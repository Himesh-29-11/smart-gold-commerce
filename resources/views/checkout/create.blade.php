@extends('layouts.app')
@section('title', 'Secure Checkout')
@section('content')
    <section class="page-hero compact">
        <span class="kicker">Final review</span>
        <h1>Secure checkout</h1>
        <p>Confirm delivery details and choose an authorized payment provider.</p>
    </section>
    <section class="section checkout-layout">
        <form class="checkout-form" method="POST" action="{{ route('checkout.store') }}">@csrf<section class="form-card">
                <div class="step-heading"><span>1</span>
                    <div>
                        <h2>Delivery information</h2>
                        <p>Use the address that should appear on dispatch records.</p>
                    </div>
                </div>
                <div class="form-grid"><label class="span-2">Full name<input name="full_name"
                            value="{{ old('full_name', auth()->user()->name) }}" required></label><label
                        class="span-2">Mobile number<input name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                            required></label><label class="span-2">Address line 1<input name="address_line_1"
                            value="{{ old('address_line_1') }}" required></label><label class="span-2">Address line 2
                        <small>(optional)</small><input name="address_line_2"
                            value="{{ old('address_line_2') }}"></label><label>City<input name="city"
                            value="{{ old('city', 'Ahmedabad') }}" required></label><label>State<input name="state"
                            value="{{ old('state', 'Gujarat') }}" required></label><label>PIN code<input name="postal_code"
                            inputmode="numeric" pattern="[1-9][0-9]{5}" value="{{ old('postal_code') }}"
                            required></label><label>Order note <small>(optional)</small><input name="notes"
                            value="{{ old('notes') }}"></label></div>
            </section>
            <section class="form-card">
                <div class="step-heading"><span>2</span>
                    <div>
                        <h2>Payment method</h2>
                        <p>Card and UPI details are entered on the provider’s secure interface.</p>
                    </div>
                </div>
                <div class="payment-options"><label><input type="radio" name="provider" value="razorpay"
                            @checked(old('provider', config('commerce.payment_provider')) === 'razorpay')><span><b>Razorpay</b><small>UPI, cards, netbanking and supported
                                wallets</small></span><strong>Secure</strong></label><label><input type="radio"
                            name="provider" value="stripe" @checked(old('provider', config('commerce.payment_provider')) === 'stripe')><span><b>Stripe</b><small>Cards and
                                payment methods enabled on your Stripe account</small></span><strong>Secure</strong></label>
                </div><label class="check"><input type="checkbox" name="terms" value="1" required> I confirm the
                    delivery details, accept the purchase terms and understand that gold prices can fluctuate.</label>
            </section><button class="button button-lg full" type="submit">Create order & continue to payment</button>
        </form>
        <aside class="order-summary checkout-summary">
            <h2>Your order</h2>
            @foreach ($quote['lines'] as $line)
                <div class="mini-item"><img src="{{ $line['product']->image_url }}"
                        alt=""><span><b>{{ $line['product']->name }}</b><small>Qty {{ $line['item']->quantity }} ·
                            {{ $line['product']->purity }}</small></span><strong>₹{{ number_format($line['total'], 2) }}</strong>
                </div>
            @endforeach
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
                        <dt>Discount</dt>
                        <dd>− ₹{{ number_format($quote['discount'], 2) }}</dd>
                    </div>
                @endif
            </dl>
            <div class="summary-total"><span>Amount payable</span><strong>₹{{ number_format($quote['total'], 2) }}</strong>
            </div>
            <div class="secure-note">⌾ <span><b>High-value order controls</b>Order values are fixed before gateway handoff.
                    Webhook signatures are verified before payment status changes.</span></div>
        </aside>
    </section>
@endsection
