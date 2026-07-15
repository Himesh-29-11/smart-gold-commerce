<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway implements PaymentGateway
{
    public function createCheckout(Order $order): array
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            throw new RuntimeException('Stripe credentials are not configured.');
        }

        $response = Http::withToken($secret)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('payments.stripe.return').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('orders.show', $order),
                'client_reference_id' => $order->reference,
                'customer_email' => $order->user->email,
                'metadata[order_reference]' => $order->reference,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => 'inr',
                'line_items[0][price_data][unit_amount]' => (int) round((float) $order->total * 100),
                'line_items[0][price_data][product_data][name]' => 'Certified gold order '.$order->reference,
            ]);
        $response->throw();
        $data = $response->json();

        if (! is_array($data) || empty($data['id']) || empty($data['url'])) {
            throw new RuntimeException('Stripe returned an incomplete checkout response.');
        }

        return [
            'provider_order_id' => $data['id'],
            'mode' => 'redirect',
            'checkout_url' => $data['url'],
        ];
    }

    public function verifyReturn(Payment $payment, array $payload): PaymentResult
    {
        $secret = (string) config('services.stripe.secret');
        $sessionId = (string) ($payload['session_id'] ?? '');
        if ($secret === '' || $sessionId === '' || $sessionId !== $payment->provider_order_id) {
            return new PaymentResult(false);
        }

        $response = Http::withToken($secret)
            ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);
        $response->throw();
        $data = $response->json();

        $expectedMinorAmount = (int) round((float) $payment->amount * 100);
        $paid = is_array($data)
            && ($data['payment_status'] ?? '') === 'paid'
            && ($data['currency'] ?? '') === strtolower($payment->currency)
            && (int) ($data['amount_total'] ?? -1) === $expectedMinorAmount;

        return new PaymentResult($paid, $data['payment_intent'] ?? $sessionId, [
            'verification' => 'stripe_session_retrieval',
            'session_id' => $sessionId,
            'payment_status' => $data['payment_status'] ?? null,
            'amount_total' => $data['amount_total'] ?? null,
            'currency' => $data['currency'] ?? null,
            'payment_intent' => $data['payment_intent'] ?? null,
        ]);
    }
}
