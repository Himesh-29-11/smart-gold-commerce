<?php

namespace Tests\Unit;

use App\Models\GoldPriceHistory;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_server_side_order_creation_snapshots_totals_and_reserves_stock(): void
    {
        $user = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
        $product = Product::where('purity', '24K')->firstOrFail();
        $startingStock = $product->stock_quantity;
        app(CartService::class)->add($user, $product, 2);

        $order = app(OrderService::class)->create($user, [
            'full_name' => 'Demo Customer',
            'phone' => '9876500002',
            'address_line_1' => 'Test address',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'postal_code' => '380001',
        ]);

        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertGreaterThan(0, (float) $order->total);
        $this->assertSame($startingStock - 2, $product->fresh()->stock_quantity);
        $this->assertSame(0, $user->cart->items()->count());
        $this->assertSame($product->sku, $order->items->first()->product_snapshot['sku']);
    }

    public function test_coupon_reduces_taxable_value_before_gst(): void
    {
        $user = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
        $product = Product::where('sku', 'BAR-24K-10G')->firstOrFail();
        $carts = app(CartService::class);
        $carts->add($user, $product);
        $cart = $carts->cartFor($user);
        $cart->update(['coupon_code' => 'WELCOME1000']);

        $quote = $carts->quote($cart);
        $expectedTax = round(($quote['subtotal'] - 1000) * 0.03, 2);

        $this->assertSame(1000.0, $quote['discount']);
        $this->assertSame($expectedTax, $quote['tax']);
        $this->assertSame(
            round($quote['subtotal'] - $quote['discount'] + $quote['tax'] + $quote['delivery'], 2),
            $quote['total'],
        );
    }

    public function test_demo_prices_block_checkout_unless_explicitly_allowed(): void
    {
        config(['gold.allow_demo_checkout' => false]);
        $user = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
        $product = Product::where('pricing_mode', 'live')->firstOrFail();
        app(CartService::class)->add($user, $product);

        $this->expectException(ValidationException::class);
        app(OrderService::class)->create($user, [
            'full_name' => 'Demo Customer',
            'phone' => '9876500002',
            'address_line_1' => 'Test address',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'postal_code' => '380001',
        ]);
    }

    public function test_stale_live_rate_blocks_order_creation(): void
    {
        config(['gold.block_stale_checkout' => true, 'gold.stale_after_minutes' => 90]);
        GoldPriceHistory::query()->update(['fetched_at' => now()->subDay()]);
        $user = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
        $product = Product::where('pricing_mode', 'live')->firstOrFail();
        app(CartService::class)->add($user, $product);

        $this->expectException(ValidationException::class);
        app(OrderService::class)->create($user, [
            'full_name' => 'Demo Customer',
            'phone' => '9876500002',
            'address_line_1' => 'Test address',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'postal_code' => '380001',
        ]);
    }
}
