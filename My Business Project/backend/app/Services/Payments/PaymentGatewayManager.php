<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\AbaPaywayGateway;
use App\Services\Payments\Gateways\BakongKhqrGateway;
use App\Services\Payments\Gateways\FakeGateway;
use App\Services\Payments\Gateways\StripeGateway;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    protected array $gateways = [];

    public function __construct(protected Container $container) {}

    public function gateway(string $name): PaymentGateway
    {
        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        $gateway = match ($name) {
            'stripe' => new StripeGateway(config('billing.gateways.stripe', [])),
            'bakong_khqr' => new BakongKhqrGateway(config('billing.gateways.bakong_khqr', [])),
            'aba_payway' => new AbaPaywayGateway(config('billing.gateways.aba_payway', [])),
            'fake' => new FakeGateway(),
            default => throw new InvalidArgumentException("Unknown payment gateway [{$name}]."),
        };

        return $this->gateways[$name] = $gateway;
    }

    public function defaultGateway(): PaymentGateway
    {
        return $this->gateway(config('billing.default_gateway', 'fake'));
    }

    /**
     * Gateways that are actually usable in this deployment.
     *
     * @return array<string, PaymentGateway>
     */
    public function availableGateways(): array
    {
        $available = [];

        foreach (['stripe', 'bakong_khqr', 'aba_payway', 'fake'] as $name) {
            $gateway = $this->gateway($name);
            if ($gateway->isConfigured()) {
                $available[$name] = $gateway;
            }
        }

        return $available;
    }
}
