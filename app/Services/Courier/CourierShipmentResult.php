<?php

namespace App\Services\Courier;

final readonly class CourierShipmentResult
{
    public function __construct(
        public string $carrier,
        public string $trackingNumber,
        public string $status,
        public ?string $publicTrackingUrl = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?\DateTimeImmutable $estimatedDeliveryAt = null,
        public array $events = [],
    ) {}
}
