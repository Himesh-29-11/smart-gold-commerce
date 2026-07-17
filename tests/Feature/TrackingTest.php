<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\ShipmentStatusNotification;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_tracking_timeline_and_approximate_location(): void
    {
        Notification::fake();
        $user = User::factory()->create(['otp_verified_at' => now(), 'email_verified_at' => now(), 'is_active' => true]);
        $order = Order::create([
            'user_id' => $user->id,
            'reference' => 'SGC-TRACK-1',
            'status' => 'shipped',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 30,
            'delivery_charge' => 0,
            'total' => 1030,
            'shipping_address' => ['full_name' => $user->name],
        ]);
        $service = app(ShipmentService::class);
        $shipment = $service->ensureForOrder($order);
        $service->recordStatus($shipment, 'in_transit', 'Package in transit', 'Courier location updated.', 23.022505, 72.571365);

        $this->actingAs($user)
            ->get(route('orders.tracking', $order))
            ->assertOk()
            ->assertSee($shipment->tracking_number)
            ->assertSee('Delivery timeline');

        $this->actingAs($user)
            ->getJson(route('orders.tracking.data', $order))
            ->assertOk()
            ->assertJsonPath('shipment.status', 'in_transit')
            ->assertJsonPath('shipment.location.latitude', 23.023)
            ->assertJsonPath('shipment.location.precision', 'approximate');

        $this->actingAs($user)
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertSee('Notifications');

        Notification::assertSentTo($user, ShipmentStatusNotification::class);
    }

    public function test_other_customer_cannot_view_tracking(): void
    {
        $owner = User::factory()->create(['otp_verified_at' => now(), 'is_active' => true]);
        $other = User::factory()->create(['otp_verified_at' => now(), 'is_active' => true]);
        $order = Order::create([
            'user_id' => $owner->id,
            'reference' => 'SGC-TRACK-PRIVATE',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 30,
            'delivery_charge' => 0,
            'total' => 1030,
            'shipping_address' => ['full_name' => $owner->name],
        ]);

        $this->actingAs($other)->get(route('orders.tracking', $order))->assertForbidden();
    }
}
