@extends('layouts.app')
@section('title', 'Shopping Bag')
@section('content')
    <section class="page-hero compact">
        <span class="kicker">Secure purchase</span>
        <h1>Your shopping bag</h1>
        <p>Prices are recalculated from the latest stored rate before checkout.</p>
    </section>
    <section class="section cart-layout">
        @if ($quote['lines'])
            <div class="cart-items">
                <div class="cart-head">
                    <h2>{{ collect($quote['lines'])->sum(fn($l) => $l['item']->quantity) }} item(s)</h2><a
                        href="{{ route('catalog.index') }}">Continue shopping</a>
                </div>
                @foreach ($quote['lines'] as $line)
                    <article class="cart-item"><img src="{{ $line['product']->image_url }}" alt="{{ $line['product']->name }}">
                        <div class="cart-product"><span class="eyebrow">{{ $line['product']->purity }} ·
                                {{ $line['product']->weight_grams }}g</span>
                            <h3><a href="{{ route('catalog.show', $line['product']) }}">{{ $line['product']->name }}</a></h3>
                            <small>{{ $line['product']->certification }}</small>
                            <div class="cart-actions">
                                <form method="POST" action="{{ route('cart.update', $line['item']) }}">@csrf
                                    @method('PATCH')<label>Quantity <input type="number" name="quantity" min="1"
                                            max="{{ min(10, $line['product']->stock_quantity) }}"
                                            value="{{ $line['item']->quantity }}"></label><button class="text-link"
                                        type="submit">Update</button></form>
                                <form method="POST" action="{{ route('cart.destroy', $line['item']) }}">@csrf
                                    @method('DELETE')<button class="text-link danger" type="submit">Remove</button></form>
                            </div>
                        </div>
                        <div class="cart-line-price">
                            <strong>₹{{ number_format($line['total'], 2) }}</strong><small>₹{{ number_format($line['unit_price'], 2) }}
                                each + GST</small></div>
                    </article>
                @endforeach
            </div>
            <aside class="order-summary">
                <h2>Order summary</h2>
                <dl>
                    <div>
                        <dt>Product value</dt>
                        <dd>₹{{ number_format($quote['subtotal'], 2) }}</dd>
                    </div>
                    <div>
                        <dt>GST</dt>
                        <dd>₹{{ number_format($quote['tax'], 2) }}</dd>
                    </div>
                    <div>
                        <dt>Delivery</dt>
                        <dd>{{ $quote['delivery'] ? '₹' . number_format($quote['delivery'], 2) : 'Complimentary' }}</dd>
                    </div>
                    @if ($quote['discount'])
                        <div class="discount">
                            <dt>Coupon discount</dt>
                            <dd>− ₹{{ number_format($quote['discount'], 2) }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="summary-total">
                    <span>Total</span><strong>₹{{ number_format($quote['total'], 2) }}</strong><small>Inclusive of
                        calculated taxes</small></div>
                @if ($quote['coupon'])
                    <form class="coupon-applied" method="POST" action="{{ route('cart.coupon.remove') }}">@csrf
                        @method('DELETE')<span>✓ {{ $quote['coupon']->code }}</span><button type="submit">Remove</button>
                </form>@else<form class="coupon-form" method="POST" action="{{ route('cart.coupon.apply') }}">
                        @csrf<label>Have a coupon?</label>
                        <div><input name="code" placeholder="Enter code"><button class="button button-outline"
                                type="submit">Apply</button></div>
                    </form>
                @endif
                <a class="button button-lg full" href="{{ route('checkout.create') }}">
                    Proceed to secure checkout</a>
                <ul class="summary-trust">
                    <li>✓ Authorized payment gateways</li>
                    <li>✓ Final rate shown before payment</li>
                    <li>✓ Itemized invoice on confirmation</li>
                </ul>
        </aside>@else<div class="empty-state wide"><span>◇</span>
                <h2>Your bag is waiting</h2>
                <p>Explore certified coins, bars and jewellery from verified partners.</p><a class="button"
                    href="{{ route('catalog.index') }}">Explore the collection</a>
            </div>
        @endif
    </section>
@endsection
