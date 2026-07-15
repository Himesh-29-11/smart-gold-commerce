<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(string $name): PaymentGateway
    {
        return match ($name) {
            'razorpay' => app(RazorpayGateway::class),'stripe' => app(StripeGateway::class),default => throw new InvalidArgumentException("Unsupported payment provider: $name")
        };
    }
}
