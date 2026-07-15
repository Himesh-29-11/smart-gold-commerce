<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RazorpayGateway implements PaymentGateway
{
    public function createCheckout(Order $order): array
    {
        $key = (string) config('services.razorpay.key');
        $secret = (string) config('services.razorpay.secret');
        if ($key === '' || $secret === '') {
            throw new RuntimeException('Razorpay credentials are not configured.');
        }

        $response = Http::withBasicAuth($key, $secret)
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => (int) round((float) $order->total * 100),
                'currency' => 'INR',
                'receipt' => $order->reference,
                'notes' => ['order_reference' => $order->reference],
            ]);
        $response->throw();
        $data = $response->json();

        if (! is_array($data) || empty($data['id']) || ! isset($data['amount'], $data['currency'])) {
            throw new RuntimeException('Razorpay returned an incomplete order response.');
        }

        return [
            'provider_order_id' => $data['id'],
            'mode' => 'razorpay',
            'public_key' => $key,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'name' => config('app.name'),
            'description' => 'Certified gold order '.$order->reference,
            'callback_url' => route('payments.razorpay.return'),
        ];
    }

    public function verifyReturn(Payment $payment, array $payload): PaymentResult
    {
        $secret = (string) config('services.razorpay.secret');
        $orderId = (string) ($payload['razorpay_order_id'] ?? '');
        $paymentId = (string) ($payload['razorpay_payment_id'] ?? '');
        $signature = (string) ($payload['razorpay_signature'] ?? '');

        if ($secret === '' || $orderId === '' || $paymentId === '' || $signature === '') {
            return new PaymentResult(false);
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);
        $paid = $orderId === $payment->provider_order_id && hash_equals($expected, $signature);

        return new PaymentResult($paid, $paymentId, [
            'verification' => 'razorpay_browser_return',
            'provider_order_id' => $orderId,
            'provider_payment_id' => $paymentId,
        ]);
    }
}
