<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderPaidNotification;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly ShipmentService $shipments,
        private readonly NotificationDeliveryService $notifications,
    ) {}

    public function initiate(Order $order, string $provider): array
    {
        $order->refresh();
        if ($order->payment_status === 'paid') {
            throw new RuntimeException('This order is already paid.');
        }
        if ($order->status !== 'pending') {
            throw new RuntimeException('Payment cannot be started for an order in its current state.');
        }

        $payment = $order->payments()->create([
            'provider' => $provider,
            'status' => 'initiated',
            'amount' => $order->total,
            'currency' => 'INR',
        ]);

        try {
            $checkout = $this->gateways->driver($provider)->createCheckout($order->loadMissing('user'));
            $payment->update([
                'provider_order_id' => $checkout['provider_order_id'],
                'provider_payload' => $checkout,
            ]);

            return compact('payment', 'checkout');
        } catch (\Throwable $e) {
            $payment->update([
                'status' => 'failed',
                'provider_payload' => ['error' => $e->getMessage()],
            ]);
            throw $e;
        }
    }

    public function verifyReturn(Payment $payment, array $payload): bool
    {
        $result = $this->gateways->driver($payment->provider)->verifyReturn($payment, $payload);

        if ($result->paid) {
            $this->markPaid($payment, $result);
        }

        return $result->paid;
    }

    public function markPaid(Payment $payment, PaymentResult $result): void
    {
        $paidOrder = DB::transaction(function () use ($payment, $result): ?Order {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'paid') {
                return null;
            }

            $lockedPayment->update([
                'status' => 'paid',
                'provider_payment_id' => $result->paymentId,
                'provider_payload' => $result->payload,
                'paid_at' => now(),
            ]);

            $order = $lockedPayment->order()->lockForUpdate()->firstOrFail();
            $wasCancelled = $order->status === 'cancelled';
            $order->update([
                'payment_status' => 'paid',
                'status' => $wasCancelled ? 'payment_review' : 'confirmed',
            ]);

            if ($order->coupon_code && ! $wasCancelled) {
                Coupon::where('code', $order->coupon_code)
                    ->lockForUpdate()
                    ->first()
                    ?->increment('used_count');
            }

            return $order;
        });

        if (! $paidOrder) {
            return;
        }

        $this->shipments->ensureForOrder($paidOrder);
        $paidOrder->load(['user', 'items', 'shipment']);
        $this->notifications->send($paidOrder->user, new OrderPaidNotification($paidOrder));
    }

    public function handleWebhook(string $provider, string $raw, array $headers): bool
    {
        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return false;
        }

        return match ($provider) {
            'razorpay' => $this->handleRazorpayWebhook($raw, $payload, $headers),
            'stripe' => $this->handleStripeWebhook($raw, $payload, $headers),
            default => false,
        };
    }

    private function handleRazorpayWebhook(string $raw, array $payload, array $headers): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $signature = $headers['x-razorpay-signature'][0]
            ?? $headers['X-Razorpay-Signature'][0]
            ?? '';
        $expected = hash_hmac('sha256', $raw, $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return false;
        }

        if (($payload['event'] ?? '') !== 'payment.captured') {
            return true;
        }

        $entity = data_get($payload, 'payload.payment.entity', []);
        $payment = Payment::where('provider', 'razorpay')
            ->where('provider_order_id', $entity['order_id'] ?? '')
            ->first();

        if (! $payment) {
            return true;
        }

        $amountMatches = (int) ($entity['amount'] ?? -1) === (int) round((float) $payment->amount * 100);
        $currencyMatches = strtoupper((string) ($entity['currency'] ?? '')) === strtoupper($payment->currency);
        if (! $amountMatches || ! $currencyMatches) {
            return false;
        }

        $this->markPaid($payment, new PaymentResult(
            paid: true,
            paymentId: $entity['id'] ?? null,
            payload: [
                'verification' => 'razorpay_signed_webhook',
                'event' => $payload['event'],
                'provider_order_id' => $entity['order_id'] ?? null,
                'provider_payment_id' => $entity['id'] ?? null,
                'amount' => $entity['amount'] ?? null,
                'currency' => $entity['currency'] ?? null,
            ],
        ));

        return true;
    }

    private function handleStripeWebhook(string $raw, array $payload, array $headers): bool
    {
        $header = $headers['stripe-signature'][0]
            ?? $headers['Stripe-Signature'][0]
            ?? '';

        if (! $this->validStripeSignature($raw, $header)) {
            return false;
        }

        if (($payload['type'] ?? '') !== 'checkout.session.completed') {
            return true;
        }

        $entity = data_get($payload, 'data.object', []);
        $payment = Payment::where('provider', 'stripe')
            ->where('provider_order_id', $entity['id'] ?? '')
            ->first();

        if (! $payment || ($entity['payment_status'] ?? '') !== 'paid') {
            return true;
        }

        $amountMatches = (int) ($entity['amount_total'] ?? -1) === (int) round((float) $payment->amount * 100);
        $currencyMatches = strtoupper((string) ($entity['currency'] ?? '')) === strtoupper($payment->currency);
        if (! $amountMatches || ! $currencyMatches) {
            return false;
        }

        $this->markPaid($payment, new PaymentResult(
            paid: true,
            paymentId: $entity['payment_intent'] ?? null,
            payload: [
                'verification' => 'stripe_signed_webhook',
                'event' => $payload['type'],
                'session_id' => $entity['id'] ?? null,
                'payment_intent' => $entity['payment_intent'] ?? null,
                'amount_total' => $entity['amount_total'] ?? null,
                'currency' => $entity['currency'] ?? null,
            ],
        ));

        return true;
    }

    private function validStripeSignature(string $raw, string $header): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key && $value) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = (int) ($parts['t'][0] ?? 0);
        if (! $timestamp || abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$raw, $secret);
        foreach ($parts['v1'] ?? [] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
