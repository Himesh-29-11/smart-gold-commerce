<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use App\Notifications\ShipmentStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentService
{
    public function __construct(private readonly NotificationDeliveryService $notifications) {}

    private const ORDER_STATUS_MAP = [
        'confirmed' => ['order_confirmed', 'Order confirmed', 'Payment is verified and the order entered fulfilment.'],
        'processing' => ['packed', 'Order packed', 'The order has been secured and prepared for dispatch.'],
        'shipped' => ['dispatched', 'Order dispatched', 'The insured delivery partner has received the package.'],
        'delivered' => ['delivered', 'Order delivered', 'Delivery was marked complete.'],
        'cancelled' => ['cancelled', 'Delivery cancelled', 'This delivery will not proceed.'],
        'payment_review' => ['exception', 'Delivery on hold', 'The order requires payment review before fulfilment.'],
    ];

    public function ensureForOrder(Order $order): Shipment
    {
        $existing = $order->shipment()->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($order): Shipment {
            $shipment = Shipment::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'tracking_number' => $this->trackingNumber(),
                    'status' => 'order_confirmed',
                ],
            );

            if ($shipment->events()->doesntExist()) {
                $shipment->events()->create([
                    'status' => 'order_confirmed',
                    'title' => 'Order confirmed',
                    'description' => 'Payment is verified and the order entered fulfilment.',
                    'occurred_at' => now(),
                ]);
            }

            return $shipment->load('order');
        });
    }

    public function syncFromOrderStatus(Order $order): ?Shipment
    {
        $event = self::ORDER_STATUS_MAP[$order->status] ?? null;
        if (! $event || $order->payment_status !== 'paid') {
            return $order->shipment;
        }

        $shipment = $this->ensureForOrder($order);
        [$status, $title, $description] = $event;

        return $this->recordStatus($shipment, $status, $title, $description);
    }

    public function recordStatus(
        Shipment $shipment,
        string $status,
        string $title,
        ?string $description = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?\DateTimeInterface $occurredAt = null,
    ): Shipment {
        $previousStatus = $shipment->status;

        DB::transaction(function () use ($shipment, $status, $title, $description, $latitude, $longitude, $occurredAt): void {
            $updates = ['status' => $status];
            if ($latitude !== null && $longitude !== null) {
                $updates += [
                    'current_latitude' => $latitude,
                    'current_longitude' => $longitude,
                    'location_updated_at' => $occurredAt ?? now(),
                ];
            }
            if ($status === 'dispatched' && ! $shipment->dispatched_at) {
                $updates['dispatched_at'] = $occurredAt ?? now();
            }
            if ($status === 'delivered') {
                $updates['delivered_at'] = $occurredAt ?? now();
            }
            $shipment->update($updates);

            $duplicate = $shipment->events()
                ->where('status', $status)
                ->where('title', $title)
                ->where('occurred_at', '>=', now()->subMinute())
                ->exists();
            if (! $duplicate) {
                $shipment->events()->create([
                    'status' => $status,
                    'title' => $title,
                    'description' => $description,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'occurred_at' => $occurredAt ?? now(),
                ]);
            }
        });

        $shipment->refresh()->load('order.user');
        if ($previousStatus !== $status) {
            $this->notifications->send($shipment->order->user, new ShipmentStatusNotification($shipment, $title));
        }

        return $shipment;
    }

    private function trackingNumber(): string
    {
        do {
            $tracking = 'NHT-'.now()->format('ymd').'-'.strtoupper(Str::random(8));
        } while (Shipment::where('tracking_number', $tracking)->exists());

        return $tracking;
    }
}
