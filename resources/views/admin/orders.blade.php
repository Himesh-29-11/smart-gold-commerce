@extends('layouts.admin')
@section('title', 'Manage Orders')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Fulfilment</span>
            <h1>Orders</h1>
            <p>Manage operational status. Payment status is changed only by verified gateway events.</p>
        </div><a class="button button-outline" href="{{ route('admin.reports.orders') }}">↓ Export CSV</a>
    </div>
    <article class="admin-panel">
        <form class="admin-search" method="GET"><select name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="button button-outline">Filter</button>
        </form>
        <div class="admin-table order-admin-table">
            <div class="table-row table-head">
                <span>Order</span><span>Customer</span><span>Total</span><span>Payment</span><span>Fulfilment
                    status</span><span>Update</span></div>
            @foreach ($orders as $order)
                <div class="table-row"><span><a
                            href="{{ route('orders.show', $order) }}"><b>{{ $order->reference }}</b></a><small>{{ $order->created_at->format('d M Y, h:i A') }}</small></span><span>{{ $order->user->name }}<small>{{ $order->user->email }}</small></span><span>₹{{ number_format($order->total, 2) }}</span><span
                        class="status status-{{ $order->payment_status }}">{{ $order->payment_status }}</span><span
                        class="status status-{{ $order->status }}">{{ $order->status }}</span>
                    <form class="inline-update" method="POST" action="{{ route('admin.orders.update', $order) }}">@csrf
                        @method('PATCH')<select name="status">
                            @foreach ($statuses as $status)
                                <option @selected($order->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Save</button>
                    </form>
                </div>
            @endforeach
        </div>{{ $orders->links() }}
    </article>
@endsection
