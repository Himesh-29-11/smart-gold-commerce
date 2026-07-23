<?php

namespace Tests\Feature;

use App\Models\GoldPriceHistory;
use App\Models\User;
use App\Services\DemoGoldPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGoldPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_monitor_and_refresh_labelled_demo_history(): void
    {
        config(['gold.provider' => 'database']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'otp_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        app(DemoGoldPriceService::class)->refresh(5);

        $this->actingAs($admin)
            ->get(route('admin.gold-prices.index'))
            ->assertOk()
            ->assertSee('Gold-rate feed')
            ->assertSee('Manual live entry')
            ->assertSee('Disabled');

        $this->actingAs($admin)
            ->post(route('admin.gold-prices.refresh-demo'), ['days' => 30])
            ->assertRedirect();

        $this->assertSame(60, GoldPriceHistory::where('source', DemoGoldPriceService::SOURCE)->count());
        $this->assertTrue(GoldPriceHistory::where('source', DemoGoldPriceService::SOURCE)->latest('fetched_at')->firstOrFail()->fetched_at->isToday());
    }

    public function test_customer_cannot_access_gold_operations(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'otp_verified_at' => now(),
        ]);

        $this->actingAs($customer)->get(route('admin.gold-prices.index'))->assertForbidden();
    }
}
