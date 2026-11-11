<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Metric formulas (documented in docs/architecture.md):
     *
     *  total_users              = COUNT(users)
     *  total_businesses         = COUNT(businesses)
     *  new_registrations        = users created in the last 30 days
     *  active_subscriptions     = subscriptions with status in (active, trialing)
     *  past_due_subscriptions   = subscriptions with status = past_due
     *  canceled_subscriptions   = subscriptions with status = canceled
     *  churn_rate               = canceled / (active + canceled) over the window
     *  mrr                      = SUM over ACTIVE subscriptions of the monthly
     *                              equivalent of the plan price.
     *                              Yearly plans are divided by 12 so that the
     *                              figure is genuinely recurring monthly revenue.
     *                              One-time payments are NOT counted.
     *  revenue_trend            = settled payments grouped by month.
     */
    public function index(): JsonResponse
    {
        $now = now();
        $windowStart = $now->copy()->subDays(30);

        $totalUsers = User::count();
        $totalBusinesses = Business::count();
        $newRegistrations = User::where('created_at', '>=', $windowStart)->count();

        $activeSubscriptions = BusinessSubscription::whereIn('status', ['active', 'trialing'])->count();
        $pastDue = BusinessSubscription::where('status', 'past_due')->count();
        $canceled = BusinessSubscription::where('status', 'canceled')->count();
        $expired = BusinessSubscription::where('status', 'expired')->count();

        $churnDenominator = $activeSubscriptions + $canceled;
        $churnRate = $churnDenominator > 0 ? round(($canceled / $churnDenominator) * 100, 2) : 0.0;

        $mrr = $this->mrr();

        $revenueTrend = Payment::where('status', 'succeeded')
            ->where('paid_at', '>=', $now->copy()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as payments")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => (int) $row->total,
                'payments' => (int) $row->payments,
            ]);

        $successCount = Payment::where('status', 'succeeded')->count();
        $failureCount = Payment::where('status', 'failed')->count();
        $totalPayments = $successCount + $failureCount;

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'total_businesses' => $totalBusinesses,
                'new_registrations' => $newRegistrations,
                'active_subscriptions' => $activeSubscriptions,
                'past_due_subscriptions' => $pastDue,
                'canceled_subscriptions' => $canceled,
                'expired_subscriptions' => $expired,
                'churn_rate' => $churnRate,
                'mrr' => $mrr,
                'revenue_trend' => $revenueTrend,
                'payments' => [
                    'succeeded' => $successCount,
                    'failed' => $failureCount,
                    'success_rate' => $totalPayments > 0 ? round(($successCount / $totalPayments) * 100, 2) : 0.0,
                ],
                'suspended_businesses' => Business::where('is_suspended', true)->count(),
            ],
        ]);
    }

    /**
     * Monthly recurring revenue in minor units. Only ACTIVE subscriptions on
     * recurring plans contribute. Yearly subscriptions are amortised over 12
     * months so that MRR reflects the true monthly recurring run rate.
     */
    protected function mrr(): int
    {
        $rows = DB::table('business_subscriptions')
            ->join('plans', 'plans.id', '=', 'business_subscriptions.plan_id')
            ->where('business_subscriptions.status', 'active')
            ->where('plans.type', 'pro')
            ->selectRaw('business_subscriptions.billing_cycle, plans.price_monthly, plans.price_yearly')
            ->get();

        $total = 0;

        foreach ($rows as $row) {
            if ($row->billing_cycle === 'yearly') {
                $total += (int) round($row->price_yearly / 12);
            } else {
                $total += (int) $row->price_monthly;
            }
        }

        return $total;
    }
}
