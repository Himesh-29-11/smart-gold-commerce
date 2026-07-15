@extends('layouts.app')
@section('title', 'Order ' . $order->reference)
@section('content')
    <section class="page-hero compact">
        <span class="kicker">Order details</span>
        <h1>{{ $order->reference }}</h1>
        <p>Placed {{ $order->created_at->format('d M Y, h:i A') }}</p>
    </section>
    <section class="section order-detail">
        <div>
            <div class="order-status-card">
                <div><span class="status status-{{ $order->status }}">{{ $order->status }}</span>
                    <h2>{{ $order->payment_status === 'paid' ? 'Payment confirmed' : 'Payment pending' }}</h2>
                    <p>{{ $order->payment_status === 'paid' ? 'Your order is now in our secure fulfilment flow.' : 'Complete payment to confirm this order.' }}
                    </p>
                </div><span class="pay-status">{{ ucfirst($order->payment_status) }}</span>
            </div>
            <div class="cart-items">
                @foreach ($order->items as $item)
                    <article class="cart-item"><img src="{{ data_get($item->product_snapshot, 'image_url') }}" alt="">
                        <div class="cart-product"><span class="eyebrow">{{ data_get($item->product_snapshot, 'purity') }} ·
                                {{ data_get($item->product_snapshot, 'weight_grams') }}g</span>
                            <h3>{{ data_get($item->product_snapshot, 'name') }}</h3>
                            <small>{{ data_get($item->product_snapshot, 'certification') }} · Qty
                                {{ $item->quantity }}</small>
                        </div>
                        <div class="cart-line-price"><strong>₹{{ number_format($item->line_total, 2) }}</strong><small>GST
                                ₹{{ number_format($item->tax_amount, 2) }}</small></div>
                    </article>
                @endforeach
            </div>
        </div>
        <aside>
            <div class="order-summary">
                <h2>Payment summary</h2>
                <dl>
                    <div>
                        <dt>Product value</dt>
                        <dd>₹{{ number_format($order->subtotal, 2) }}</dd>
                    </div>
                    <div>
                        <dt>GST</dt>
                        <dd>₹{{ number_format($order->tax, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Delivery</dt>
                        <dd>₹{{ number_format($order->delivery_charge, 2) }}</dd>
                    </div>
                    <div>
                        <dt>Discount</dt>
                        <dd>− ₹{{ number_format($order->discount, 2) }}</dd>
                    </div>
                </dl>
                <div class="summary-total"><span>Total</span><strong>₹{{ number_format($order->total, 2) }}</strong></div>
                @if ($order->payment_status === 'paid')
                    <a class="button button-outline full" href="{{ route('orders.invoice', $order) }}" target="_blank">View
                        / print invoice</a>
                @elseif($order->status !== 'cancelled')
                    <form method="POST" action="{{ route('payments.retry', $order) }}">@csrf<label>Payment provider<select
                                name="provider">
                                <option value="razorpay">Razorpay</option>
                                <option value="stripe">Stripe</option>
                            </select></label><button class="button full" type="submit">Retry payment</button></form>
                @endif
            </div>
            <div class="address-card">
                <h3>Delivery address</h3>
                <p><b>{{ data_get($order->shipping_address, 'full_name') }}</b><br>{{ data_get($order->shipping_address, 'address_line_1') }}<br>
                    @if (data_get($order->shipping_address, 'address_line_2'))
                        {{ data_get($order->shipping_address, 'address_line_2') }}
                        <br>
                    @endif{{ data_get($order->shipping_address, 'city') }},
                    {{ data_get($order->shipping_address, 'state') }}
                    {{ data_get($order->shipping_address, 'postal_code') }}<br>{{ data_get($order->shipping_address, 'phone') }}
                </p>
            </div>
        </aside>
    </section>
@endsection
