@extends('layouts.app')
@section('title', 'My Account')
@section('content')
    <section class="account-hero">
        <div><span class="kicker">Private account</span>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                {{ explode(' ', auth()->user()->name)[0] }}.</h1>
            <p>Review orders, financing requests and saved gold.</p>
        </div>
        <div class="account-badge"><span>✓</span>
            <div><b>Verified account</b><small>{{ auth()->user()->email }}</small></div>
        </div>
    </section>
    <section class="section account-grid">
        <aside class="account-nav"><a class="active" href="{{ route('account.dashboard') }}">Overview</a><a
                href="{{ route('orders.index') }}">Orders</a><a href="{{ route('wishlist.index') }}">Wishlist</a><a
                href="{{ route('loans.index') }}#eligibility">Financing requests</a><a
                href="{{ route('cart.index') }}">Shopping bag</a></aside>
        <div class="account-content">
            <div class="section-heading">
                <h2>Recent orders</h2><a href="{{ route('orders.index') }}">View all</a>
            </div>
            @forelse($orders as $order)
                <a class="order-row" href="{{ route('orders.show', $order) }}">
                    <div><b>{{ $order->reference }}</b><span>{{ $order->created_at->format('d M Y') }} ·
                            {{ $order->items->sum('quantity') }} item(s)</span></div>
                    <strong>₹{{ number_format($order->total, 2) }}</strong><span
                        class="status status-{{ $order->status }}">{{ $order->status }}</span><i>→</i>
            </a>@empty<div class="empty-state">
                    <p>No orders yet.</p><a class="button" href="{{ route('catalog.index') }}">Browse gold</a>
                </div>
            @endforelse
            <div class="section-heading account-subhead">
                <h2>Financing requests</h2><a href="{{ route('loans.index') }}">View assistance page</a>
            </div>
            @forelse($loans as $loan)
                <div class="order-row">
                    <div><b>{{ $loan->reference }}</b><span>{{ $loan->partner?->name }}</span></div>
                    <strong>₹{{ number_format($loan->requested_amount) }}</strong><span
                        class="status status-{{ $loan->status }}">{{ str_replace('_', ' ', $loan->status) }}</span>
            </div>@empty<p class="muted">No financing requests.</p>
            @endforelse
        </div>
    </section>
@endsection
