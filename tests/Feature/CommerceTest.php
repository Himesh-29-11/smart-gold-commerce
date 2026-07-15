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
        $this->get('/gold')->assertOk()->assertSee('Find your gold');
        $this->get('/gold-prices')
            ->assertOk()
            ->assertSee('Gold prices, in perspective')
            ->assertSee('Gold price trend')
            ->assertSee('data-range="1m"', escape: false)
            ->assertSee('INR per 10 grams');
        $this->getJson(route('gold-prices.data', ['range' => '5d']))
            ->assertOk()
            ->assertJsonPath('range', '5d')
            ->assertJsonPath('chart_unit_grams', 10)
            ->assertJsonCount(5, 'history.24K')
            ->assertJsonStructure([
                'currency',
                'unit',
                'source',
                'server_time',
                'poll_after_seconds',
                'rates' => ['22K', '24K'],
                'history' => ['22K', '24K'],
            ]);
        $this->get('/gold-loan-assistance')->assertOk()->assertSee('We connect. We do not lend');
    }

    public function test_verified_customer_can_add_and_update_cart(): void
    {
        $user = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
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
        $user = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
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
        $customer = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
        $admin = User::where('email', 'admin@aurumtrust.test')->firstOrFail();

        $this->get('/login')->assertOk()->assertSee('Sign in securely');
        $this->get('/register')->assertOk()->assertSee('Create secure account');
        $this->get(route('catalog.show', $product))->assertOk()->assertSee($product->name);
        $this->actingAs($customer)->get(route('account.dashboard'))->assertOk()->assertSee('Recent orders');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Commerce pulse');
        $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->assertSee('Control certification details');
        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('Gallery media JSON');
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk()->assertSee('Manage operational status');
        $this->actingAs($admin)->get(route('admin.loans.index'))->assertOk()->assertSee('N & H Trust is a connector', escape: false);
        $this->actingAs($admin)->get(route('admin.customers.index'))->assertOk()->assertSee('Customers');
    }

    public function test_customer_cannot_open_another_customers_order(): void
    {
        $customer = User::where('email', 'customer@aurumtrust.test')->firstOrFail();
        $other = User::factory()->create(['otp_verified_at' => now(), 'email_verified_at' => now()]);
        $order = $other->orders()->create([
            'reference' => 'SGC-TEST-ORDER', 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal' => 1000, 'discount' => 0, 'tax' => 30, 'delivery_charge' => 0, 'total' => 1030,
            'shipping_address' => ['full_name' => 'Test'],
        ]);

        $this->actingAs($customer)->get(route('orders.show', $order))->assertForbidden();
    }
}
