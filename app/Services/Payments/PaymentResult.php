<?php

namespace App\Services\Payments;

final readonly class PaymentResult
{
    public function __construct(public bool $paid, public ?string $paymentId = null, public array $payload = []) {}
}
