<?php

namespace Tests\Unit;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_razorpay_webhook_marks_payment_paid(): void
    {
        Notification::fake();
        Mail::fake();
        config(['services.razorpay.webhook_secret' => 'test-secret']);
        $user = User::factory()->create(['otp_verified_at' => now()]);
        $order = Order::create([
            'user_id' => $user->id, 'reference' => 'SGC-WEBHOOK-1', 'status' => 'pending',
            'payment_status' => 'unpaid', 'subtotal' => 1000, 'discount' => 0, 'tax' => 30,
            'delivery_charge' => 0, 'total' => 1030, 'shipping_address' => ['full_name' => 'Test'],
        ]);
        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'razorpay', 'provider_order_id' => 'order_123',
            'status' => 'initiated', 'amount' => 1030, 'currency' => 'INR',
        ]);
        $raw = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_123', 'order_id' => 'order_123', 'amount' => 103000, 'currency' => 'INR']]]], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-secret');

        $service = app(PaymentService::class);
        $this->assertTrue($service->handleWebhook('razorpay', $raw, ['x-razorpay-signature' => [$signature]]));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('shipments', ['order_id' => $order->id, 'status' => 'order_confirmed']);
        Mail::assertQueued(OrderInvoiceMail::class, fn ($mail) => $mail->order->is($order));
    }

    public function test_valid_stripe_webhook_checks_signature_amount_and_currency(): void
    {
        Notification::fake();
        Mail::fake();
        config(['services.stripe.webhook_secret' => 'stripe-test-secret']);
        $user = User::factory()->create(['otp_verified_at' => now()]);
        $order = Order::create([
            'user_id' => $user->id, 'reference' => 'SGC-STRIPE-1', 'status' => 'pending',
            'payment_status' => 'unpaid', 'subtotal' => 1000, 'discount' => 0, 'tax' => 30,
            'delivery_charge' => 0, 'total' => 1030, 'shipping_address' => ['full_name' => 'Test'],
        ]);
        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'stripe', 'provider_order_id' => 'cs_123',
            'status' => 'initiated', 'amount' => 1030, 'currency' => 'INR',
        ]);
        $raw = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_123', 'payment_status' => 'paid', 'payment_intent' => 'pi_123',
                'amount_total' => 103000, 'currency' => 'inr',
            ]],
        ], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$raw, 'stripe-test-secret');

        $service = app(PaymentService::class);
        $this->assertTrue($service->handleWebhook('stripe', $raw, ['stripe-signature' => ["t={$timestamp},v1={$signature}"]]));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        config(['services.razorpay.webhook_secret' => 'test-secret']);
        $service = app(PaymentService::class);
        $raw = json_encode(['event' => 'payment.captured'], JSON_THROW_ON_ERROR);
        $this->assertFalse($service->handleWebhook('razorpay', $raw, ['x-razorpay-signature' => ['bad']]));
    }
}
