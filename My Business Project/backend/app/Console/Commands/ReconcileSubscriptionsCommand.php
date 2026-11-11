<?php

namespace App\Console\Commands;

use App\Models\BusinessSubscription;
use App\Services\Billing\SubscriptionService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles local subscription state with the payment provider.
 *
 * The provider is the authority. A subscription is never expired because a
 * webhook was late: when the provider still reports the subscription as
 * active the local state is repaired instead.
 */
class ReconcileSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:reconcile {--provider= : Only reconcile a single provider}';

    protected $description = 'Reconcile local subscription state with the payment provider';

    public function handle(PaymentGatewayManager $gateways, SubscriptionService $subscriptions): int
    {
        $reconciled = 0;
        $skipped = 0;

        $query = BusinessSubscription::withoutGlobalScopes()
            ->whereNotNull('provider')
            ->whereNotNull('provider_subscription_id')
            ->whereIn('status', ['active', 'past_due', 'canceled', 'expired', 'trialing']);

        if ($provider = $this->option('provider')) {
            $query->where('provider', $provider);
        }

        $query->chunkById(100, function ($chunk) use ($gateways, $subscriptions, &$reconciled, &$skipped) {
            foreach ($chunk as $subscription) {
                try {
                    $gateway = $gateways->gateway((string) $subscription->provider);
                } catch (\InvalidArgumentException) {
                    $skipped++;

                    continue;
                }

                if (! $gateway->supportsManagedSubscriptions() || ! $gateway->isConfigured()) {
                    $skipped++;

                    continue;
                }

                $state = $gateway->fetchSubscription((string) $subscription->provider_subscription_id);

                if ($state === null) {
                    // The provider could not be queried. Unknown state is never
                    // treated as "canceled".
                    $skipped++;

                    continue;
                }

                $business = $subscription->business;

                if (! $business) {
                    continue;
                }

                $this->apply($subscriptions, $subscription, $business, $state);
                $reconciled++;
            }
        });

        $this->info("Reconciled {$reconciled} subscription(s), skipped {$skipped}.");

        return self::SUCCESS;
    }

    protected function apply(SubscriptionService $subscriptions, BusinessSubscription $subscription, $business, array $state): void
    {
        $status = (string) $state['status'];

        if (in_array($status, ['active', 'trialing'], true)) {
            if (in_array($subscription->status, ['expired', 'canceled', 'past_due'], true)) {
                $subscriptions->transition(
                    $business,
                    $subscription->plan,
                    $status === 'trialing' ? 'trialing' : 'active',
                    [
                        'ends_at' => $state['ends_at'] ? date('Y-m-d H:i:s', strtotime((string) $state['ends_at'])) : $subscription->ends_at,
                    ],
                    null,
                    'Reconciled with payment provider'
                );
            }

            return;
        }

        if ($status === 'canceled' && $subscription->status !== 'canceled' && $subscription->status !== 'expired') {
            $subscriptions->cancel($business, true, null, 'Provider reported the subscription as canceled');
        }
    }
}
