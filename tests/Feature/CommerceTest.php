<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\LoanRequest;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_commerce_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Gold you can trust');
        $this->get('/gold')
            ->assertOk()
            ->assertSee('Find your gold')
            ->assertSee('catalog-shell', escape: false)
            ->assertSee('catalog-grid', escape: false)
            ->assertSee('Show matching gold');
        $this->get('/gold-prices')
            ->assertOk()
            ->assertSee('Gold prices, in perspective')
            ->assertSee('Gold price trend')
            ->assertSee('market-summary-grid', escape: false)
            ->assertSee('trend-chart-stage', escape: false)
            ->assertSee('data-range="1m"', escape: false)
            ->assertSee('INR per 10 grams');
        $this->getJson(route('gold-prices.data', ['range' => '5d']))
            ->assertOk()
            ->assertJsonPath('range', '5d')
            ->assertJsonPath('chart_unit_grams', 10)
            ->assertJsonPath('mode', 'demo')
            ->assertJsonPath('is_demo', true)
            ->assertJsonPath('coverage.through_today', true)
            ->assertJsonCount(5, 'history.24K')
            ->assertJsonStructure([
                'currency',
                'unit',
                'mode',
                'is_demo',
                'disclaimer',
                'source',
                'server_time',
                'server_date',
                'coverage' => ['from', 'to', 'through_today', 'points'],
                'signal' => ['label', 'trend', 'change_percent'],
                'poll_after_seconds',
                'rates' => ['22K', '24K'],
                'history' => ['22K', '24K'],
            ]);
        $this->get('/gold-loan-assistance')->assertOk()->assertSee('We connect. We do not lend');
    }

    public function test_catalog_filters_keep_the_shop_layout_and_results_consistent(): void
    {
        $this->get(route('catalog.index', ['purity' => '24K']))
            ->assertOk()
            ->assertSee('Purity: 24K')
            ->assertSee('active-filter-list', escape: false)
            ->assertDontSee('Aarohi 22K Gold Necklace');

        $this->assertSame(
            '/images/products/lakshmi_coin.png',
            Product::where('sku', 'COIN-24K-1G')->firstOrFail()->image_url,
        );
    }

    public function test_verified_customer_can_add_and_update_cart(): void
    {
        $user = User::where('email', 'customer@nhtrust.test')->firstOrFail();
        $product = Product::firstOrFail();

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect();
        $item = CartItem::firstOrFail();
        $this->assertSame(2, $item->quantity);

        $this->actingAs($user)->patch(route('cart.update', $item), ['quantity' => 3])->assertRedirect();
        $this->assertSame(3, $item->fresh()->quantity);
        $this->actingAs($user)->get(route('cart.index'))->assertOk()->assertSee('Order summary');
    }

    public function test_customer_can_submit_a_consent_based_loan_request(): void
    {
        $user = User::where('email', 'customer@nhtrust.test')->firstOrFail();
        $partner = Partner::where('type', 'loan')->firstOrFail();

        $response = $this->actingAs($user)->post(route('loans.store'), [
            'partner_id' => $partner->id,
            'monthly_income' => 100000,
            'employment_type' => 'salaried',
            'requested_amount' => 200000,
            'tenure_months' => 24,
            'existing_monthly_emi' => 5000,
            'documents' => ['pan', 'income'],
            'consent' => '1',
        ]);

        $response->assertRedirect(route('loans.index'));
        $this->assertDatabaseHas('loan_requests', ['user_id' => $user->id, 'partner_id' => $partner->id, 'status' => 'submitted']);
        $this->assertGreaterThan(0, (float) LoanRequest::firstOrFail()->estimated_emi);
    }

    public function test_auth_product_and_admin_screens_render(): void
    {
        $product = Product::firstOrFail();
        $customer = User::where('email', 'customer@nhtrust.test')->firstOrFail();
        $admin = User::where('email', 'admin@nhtrust.test')->firstOrFail();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in securely')
            ->assertSee('data-password-toggle', escape: false)
            ->assertSee('aria-controls="login-password"', escape: false);
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create secure account')
            ->assertSee('aria-controls="register-password"', escape: false)
            ->assertSee('aria-controls="register-password-confirmation"', escape: false);
        $this->get(route('catalog.show', $product))->assertOk()->assertSee($product->name);
        $this->actingAs($customer)->get(route('account.dashboard'))->assertOk()->assertSee('Recent orders');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Commerce overview');
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('Manage product evidence');
        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('Product image & media', escape: false)->assertSee('Add gallery media');
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk()->assertSee('Payment state comes only');
        $this->actingAs($admin)->get(route('admin.loans.index'))->assertOk()->assertSee('Connector boundary');
        $this->actingAs($admin)->get(route('admin.customers.index'))->assertOk()->assertSee('Review account activity');

        $this->actingAs($admin)->get(route('admin.products.index', ['stock' => 'low']))->assertOk();
        $this->actingAs($admin)->get(route('admin.orders.index', ['payment' => 'unpaid']))->assertOk();
        $this->actingAs($admin)->get(route('admin.customers.index', ['access' => 'active']))->assertOk();
    }

    public function test_customer_cannot_open_another_customers_order(): void
    {
        $customer = User::where('email', 'customer@nhtrust.test')->firstOrFail();
        $other = User::factory()->create(['otp_verified_at' => now(), 'email_verified_at' => now()]);
        $order = $other->orders()->create([
            'reference' => 'SGC-TEST-ORDER', 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal' => 1000, 'discount' => 0, 'tax' => 30, 'delivery_charge' => 0, 'total' => 1030,
            'shipping_address' => ['full_name' => 'Test'],
        ]);

        $this->actingAs($customer)->get(route('orders.show', $order))->assertForbidden();
    }
}
