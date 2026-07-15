<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, CartService $carts): View|RedirectResponse
    {
        $cart = $carts->cartFor($request->user());
        $quote = $carts->quote($cart);

        if (! $quote['lines']) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        return view('checkout.create', compact('quote'));
    }

    public function store(
        Request $request,
        OrderService $orders,
        PaymentService $payments,
    ): View|RedirectResponse {
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:200',
            'address_line_2' => 'nullable|string|max:200',
            'city' => 'required|string|max:80',
            'state' => 'required|string|max:80',
            'postal_code' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'notes' => 'nullable|string|max:500',
            'provider' => 'required|in:razorpay,stripe',
            'terms' => 'accepted',
        ]);

        $address = collect($data)->only([
            'full_name',
            'phone',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
        ])->all();

        $order = $orders->create($request->user(), $address, $data['notes'] ?? null);

        return $this->startPayment($order, $data['provider'], $payments);
    }

    public function retry(
        Request $request,
        Order $order,
        PaymentService $payments,
    ): View|RedirectResponse {
        $this->authorizeOrder($request, $order);
        $data = $request->validate(['provider' => 'required|in:razorpay,stripe']);

        return $this->startPayment($order, $data['provider'], $payments);
    }

    public function razorpayReturn(Request $request, PaymentService $service): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);
        $payment = Payment::where('provider', 'razorpay')
            ->where('provider_order_id', $data['razorpay_order_id'])
            ->firstOrFail();
        $this->authorizeOrder($request, $payment->order);

        if ($service->verifyReturn($payment, $data)) {
            return redirect()->route('orders.show', $payment->order)
                ->with('success', 'Payment confirmed. Your order is being prepared.');
        }

        return redirect()->route('orders.show', $payment->order)
            ->withErrors(['payment' => 'We could not verify the payment signature.']);
    }

    public function stripeReturn(Request $request, PaymentService $service): RedirectResponse
    {
        $data = $request->validate(['session_id' => 'required|string']);
        $payment = Payment::where('provider', 'stripe')
            ->where('provider_order_id', $data['session_id'])
            ->firstOrFail();
        $this->authorizeOrder($request, $payment->order);

        if ($service->verifyReturn($payment, $data)) {
            return redirect()->route('orders.show', $payment->order)
                ->with('success', 'Payment confirmed. Your order is being prepared.');
        }

        return redirect()->route('orders.show', $payment->order)
            ->withErrors(['payment' => 'Payment has not been confirmed.']);
    }

    public function webhook(
        Request $request,
        string $provider,
        PaymentService $service,
    ): JsonResponse {
        abort_unless(in_array($provider, ['razorpay', 'stripe'], true), 404);
        $verified = $service->handleWebhook(
            $provider,
            $request->getContent(),
            $request->headers->all(),
        );

        return response()->json(['received' => $verified], $verified ? 200 : 400);
    }

    private function startPayment(
        Order $order,
        string $provider,
        PaymentService $payments,
    ): View|RedirectResponse {
        try {
            $result = $payments->initiate($order, $provider);

            return view('checkout.pay', [
                'order' => $order,
                'payment' => $result['payment'],
                'checkout' => $result['checkout'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('orders.show', $order)->withErrors([
                'payment' => 'Unable to start payment. Your order is saved; please try again later.',
            ]);
        }
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
    }
}
