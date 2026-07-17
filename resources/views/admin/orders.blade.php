@extends('layouts.admin')
@section('title', 'Manage Orders')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Fulfilment operations</span><h1>Orders</h1><p>Payment state comes only from verified gateway events; staff manage fulfilment separately.</p></div>
        <a class="button button-outline" href="{{ route('admin.reports.orders') }}">↓ Export CSV</a>
    </div>

    <section class="admin-panel">
        <form class="admin-search" method="GET" action="{{ route('admin.orders.index') }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Order, customer or email">
            <select name="status" aria-label="Fulfilment status"><option value="">All fulfilment</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select>
            <select name="payment" aria-label="Payment status"><option value="">All payments</option><option value="paid" @selected(request('payment') === 'paid')>Paid</option><option value="unpaid" @selected(request('payment') === 'unpaid')>Unpaid</option></select>
            <button class="button button-outline" type="submit">Filter</button><a class="admin-filter-clear" href="{{ route('admin.orders.index') }}">Clear</a>
        </form>

        <div class="admin-table order-admin-table">
            <div class="table-row table-head"><span>Order</span><span>Customer</span><span>Total</span><span>Payment</span><span>Fulfilment</span><span>Update</span></div>
            @forelse ($orders as $order)
                <div class="table-row">
                    <span><a href="{{ route('orders.show', $order) }}"><b>{{ $order->reference }}</b></a><small>{{ $order->created_at->format('d M Y, h:i A') }}</small></span>
                    <span><b>{{ $order->user->name }}</b><small>{{ $order->user->email }}</small></span>
                    <span><b>₹{{ number_format($order->total, 2) }}</b></span>
                    <span class="status status-{{ $order->payment_status }}">{{ $order->payment_status }}</span>
                    <span class="status status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                    <form class="inline-update" method="POST" action="{{ route('admin.orders.update', $order) }}">@csrf @method('PATCH')<select name="status" aria-label="Update {{ $order->reference }} status">@foreach ($statuses as $status)<option value="{{ $status }}" @selected($order->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select><button type="submit">Save</button></form>
                </div>
            @empty
                <div class="admin-empty"><strong>No orders found</strong><span>There are no orders matching these filters.</span></div>
            @endforelse
        </div>
        @include('admin.partials.pagination', ['paginator' => $orders])
    </section>
@endsection
