@extends('layouts.app')
@section('title', 'Complete Payment')
@section('content')
    <section class="simple-auth">
        <div class="auth-card payment-card"><span class="otp-icon">⌁</span><span class="kicker dark">Order
                {{ $order->reference }}</span>
            <h1>Complete your payment</h1>
            <p>Amount payable</p>
            <div class="pay-amount">₹{{ number_format($order->total, 2) }}</div>
            <p>You are using <strong>{{ ucfirst($payment->provider) }}</strong>. Do not refresh during provider
                confirmation.</p>
            @if ($checkout['mode'] === 'razorpay')
                <button id="payNow" class="button button-lg full" type="button">Open secure Razorpay checkout</button>
                <form id="razorpayReturn" method="POST" action="{{ route('payments.razorpay.return') }}">@csrf<input
                        type="hidden" name="razorpay_order_id"><input type="hidden" name="razorpay_payment_id"><input
                    type="hidden" name="razorpay_signature"></form>@else<a id="stripePay" class="button button-lg full"
                    href="{{ $checkout['checkout_url'] }}">Continue to Stripe</a>
            @endif
            <a class="text-link center" href="{{ route('orders.show', $order) }}">Return to order</a><small>Payment is marked
                paid only after a verified provider callback or signed webhook.</small>
        </div>
    </section>
@endsection
@if ($checkout['mode'] === 'razorpay')
    @push('scripts')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            document.getElementById('payNow').addEventListener('click', () => {
                const options = {
                    key: @json($checkout['public_key']),
                    amount: @json($checkout['amount']),
                    currency: @json($checkout['currency']),
                    name: @json($checkout['name']),
                    description: @json($checkout['description']),
                    order_id: @json($checkout['provider_order_id']),
                    prefill: {
                        name: @json(auth()->user()->name),
                        email: @json(auth()->user()->email),
                        contact: @json(auth()->user()->phone)
                    },
                    theme: {
                        color: '#173c34'
                    },
                    handler: function(r) {
                        const form = document.getElementById('razorpayReturn');
                        Object.keys(r).forEach(k => {
                            const input = form.querySelector('[name="' + k + '"]');
                            if (input) input.value = r[k]
                        });
                        form.submit()
                    }
                };
                new Razorpay(options).open()
            });
        </script>
    @endpush
@endif
