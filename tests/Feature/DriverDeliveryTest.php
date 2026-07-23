<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DriverDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_driver_and_driver_sends_real_location(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin', 'otp_verified_at' => now(), 'is_active' => true]);
        $driver = User::factory()->create(['role' => 'driver', 'otp_verified_at' => now(), 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'otp_verified_at' => now(), 'is_active' => true]);
        $order = Order::create([
            'user_id' => $customer->id,
            'reference' => 'SGC-DRIVER-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 30,
            'delivery_charge' => 0,
            'total' => 1030,
            'shipping_address' => ['full_name' => $customer->name, 'address_line_1' => 'Test', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'postal_code' => '380001', 'phone' => '9876543210'],
        ]);
        $shipment = app(ShipmentService::class)->ensureForOrder($order);

        $this->actingAs($admin)->post(route('admin.shipments.assign', $shipment), ['driver_id' => $driver->id])->assertRedirect();
        $assignment = $shipment->assignment()->firstOrFail();
        $this->assertSame($driver->id, $assignment->driver_id);

        $this->actingAs($driver)->post(route('driver.deliveries.accept', $assignment))->assertRedirect();
        $this->actingAs($driver)->postJson(route('driver.deliveries.location', $assignment), [
            'latitude' => 23.022505,
            'longitude' => 72.571365,
            'accuracy' => 12.5,
            'heading' => 90,
            'speed' => 7.2,
        ])->assertOk()->assertJsonPath('status', 'out_for_delivery');

        $this->assertDatabaseHas('shipment_locations', ['shipment_id' => $shipment->id, 'driver_id' => $driver->id]);
        $this->assertSame('out_for_delivery', $shipment->fresh()->status);
        $this->assertSame('active', $assignment->fresh()->status);
    }

    public function test_customer_cannot_access_driver_portal(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'otp_verified_at' => now(), 'is_active' => true]);
        $this->actingAs($customer)->get(route('driver.dashboard'))->assertForbidden();
    }
}
