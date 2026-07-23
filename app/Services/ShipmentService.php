<?php

namespace App\Services;

use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Models\User;
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

    public function assignDriver(Shipment $shipment, User $driver, User $admin): DeliveryAssignment
    {
        abort_unless($driver->isDriver() && $driver->is_active, 422);

        $assignment = DeliveryAssignment::updateOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'driver_id' => $driver->id,
                'assigned_by' => $admin->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'accepted_at' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
        );

        $this->recordStatus($shipment, 'assigned', 'Driver assigned', 'A verified N & H delivery person has been assigned.');

        return $assignment;
    }

    public function updateAssignmentStatus(DeliveryAssignment $assignment, string $status): DeliveryAssignment
    {
        $updates = ['status' => $status];
        if ($status === 'accepted') {
            $updates['accepted_at'] = now();
        } elseif ($status === 'active') {
            $updates['started_at'] = now();
        } elseif ($status === 'completed') {
            $updates['completed_at'] = now();
        }
        $assignment->update($updates);

        $shipment = $assignment->shipment;
        if ($status === 'accepted') {
            $this->recordStatus($shipment, 'driver_assigned', 'Driver accepted delivery', 'The assigned delivery person accepted this shipment.');
        } elseif ($status === 'active') {
            $this->recordStatus($shipment, 'out_for_delivery', 'Out for delivery', 'Approximate live location is now available while the driver page remains active.');
        } elseif ($status === 'completed') {
            $this->recordStatus($shipment, 'delivered', 'Order delivered', 'The assigned driver marked this delivery complete.');
            $shipment->order()->update(['status' => 'delivered']);
        }

        return $assignment->refresh();
    }

    public function recordDriverLocation(DeliveryAssignment $assignment, User $driver, array $data): ShipmentLocation
    {
        abort_unless($assignment->driver_id === $driver->id, 403);
        abort_unless(in_array($assignment->status, ['accepted', 'active'], true), 422);

        if ($assignment->status !== 'active') {
            $this->updateAssignmentStatus($assignment, 'active');
        }

        $location = ShipmentLocation::create([
            'shipment_id' => $assignment->shipment_id,
            'driver_id' => $driver->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'heading' => $data['heading'] ?? null,
            'speed' => $data['speed'] ?? null,
            'recorded_at' => now(),
        ]);

        $assignment->shipment()->update([
            'current_latitude' => $data['latitude'],
            'current_longitude' => $data['longitude'],
            'location_updated_at' => now(),
            'status' => 'out_for_delivery',
        ]);

        return $location;
    }

    private function trackingNumber(): string
    {
        do {
            $tracking = 'NHT-'.now()->format('ymd').'-'.strtoupper(Str::random(8));
        } while (Shipment::where('tracking_number', $tracking)->exists());

        return $tracking;
    }
}
