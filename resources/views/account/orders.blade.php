@extends('layouts.app')
@section('title', 'Order History')
@section('content')
    <section class="page-hero compact"><span class="kicker">Purchase records</span>
        <h1>Order history</h1>
        <p>Track status and access itemized invoices for paid orders.</p>
    </section>
    <section class="section narrow">
        <div class="account-content">
            @forelse($orders as $order)
                <a class="order-row" href="{{ route('orders.show', $order) }}">
                    <div><b>{{ $order->reference }}</b><span>{{ $order->created_at->format('d M Y, h:i A') }} ·
                            {{ $order->items->sum('quantity') }} item(s)</span></div>
                    <strong>₹{{ number_format($order->total, 2) }}</strong><span
                        class="status status-{{ $order->status }}">{{ $order->status }}</span><i>→</i>
            </a>
            @empty<div class="empty-state">
                    <h2>No orders yet</h2><a class="button" href="{{ route('catalog.index') }}">Browse collection</a>
                </div>
            @endforelse{{ $orders->links() }}
        </div>
    </section>
@endsection
