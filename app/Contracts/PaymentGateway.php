<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentResult;

interface PaymentGateway
{
    public function createCheckout(Order $order): array;

    public function verifyReturn(Payment $payment, array $payload): PaymentResult;
}
