<?php

namespace App\Services;

use App\Services\PaymentGateways\ManualGateway;
use App\Services\PaymentGateways\PaymentGateway;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resolves the active payment gateway driver by name. Register additional
 * gateways (Bakong, ACLEDA, Wing, etc.) here without touching business logic.
 */
final class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function __construct(?PaymentGateway $default = null)
    {
        $this->gateways[$default?->identifier() ?? 'manual'] = $default ?? new ManualGateway;
    }

    public function register(string $name, PaymentGateway $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    public function get(string $name): PaymentGateway
    {
        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not registered.");
        }

        return $this->gateways[$name];
    }

    /**
     * The gateway configured for the application, falling back to manual.
     */
    public function driver(?string $name = null): PaymentGateway
    {
        $driver = $name ?? config('sms.payment_gateway', 'manual');

        if (! isset($this->gateways[$driver])) {
            throw new RuntimeException("Payment gateway driver [{$driver}] is not configured.");
        }

        return $this->gateways[$driver];
    }
}
