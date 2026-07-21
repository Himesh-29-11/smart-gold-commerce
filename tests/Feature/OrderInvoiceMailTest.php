<?php

namespace Tests\Feature;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInvoiceMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_mailable_renders_branded_html_and_pdf_attachment(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'reference' => 'SGC-MAIL-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 300,
            'delivery_charge' => 0,
            'total' => 10300,
            'shipping_address' => ['full_name' => $user->name, 'address_line_1' => 'Test address', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'postal_code' => '380001'],
        ]);
        $order->items()->create([
            'product_snapshot' => ['name' => 'Certified Gold Coin', 'sku' => 'MAIL-COIN', 'purity' => '24K', 'weight_grams' => 1, 'certification' => 'Test certificate', 'image_url' => '/images/products/gold-coin.jpg'],
            'quantity' => 1,
            'unit_price' => 10000,
            'tax_amount' => 300,
            'line_total' => 10300,
        ]);
        app(ShipmentService::class)->ensureForOrder($order);
        $mail = new OrderInvoiceMail($order);

        $mail->assertSeeInHtml('N &amp; H', false);
        $mail->assertSeeInHtml('Certified Gold Coin');
        $mail->assertSeeInHtml('SGC-MAIL-1');

        $attachment = $mail->attachments()[0];
        $pdf = $attachment->attachWith(
            fn () => null,
            fn ($data) => $data(),
        );

        $this->assertSame('invoice-SGC-MAIL-1.pdf', $attachment->as);
        $this->assertSame('application/pdf', $attachment->mime);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
