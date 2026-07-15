@extends('layouts.admin')
@section('title', 'Customers')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Customer operations</span>
            <h1>Customers</h1>
            <p>Review account state and activity without exposing passwords or payment credentials.</p>
        </div>
    </div>
    <article class="admin-panel">
        <form class="admin-search" method="GET"><input type="search" name="q" value="{{ request('q') }}"
                placeholder="Name or email"><button class="button button-outline">Search</button></form>
        <div class="admin-table customer-table">
            <div class="table-row table-head"><span>Customer</span><span>Verified</span><span>Orders</span><span>Loan
                    requests</span><span>Joined</span><span>Access</span></div>
            @foreach ($customers as $customer)
                <div class="table-row"><span><b>{{ $customer->name }}</b><small>{{ $customer->email }} ·
                            {{ $customer->phone ?: 'No phone' }}</small></span><span
                        class="status {{ $customer->otp_verified_at ? 'status-confirmed' : 'status-pending' }}">{{ $customer->otp_verified_at ? 'Verified' : 'Pending' }}</span><span>{{ $customer->orders_count }}</span><span>{{ $customer->loan_requests_count }}</span><span>{{ $customer->created_at->format('d M Y') }}</span>
                    <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}">@csrf
                        @method('PATCH')<button class="{{ $customer->is_active ? 'danger-link' : 'success-link' }}"
                            type="submit">{{ $customer->is_active ? 'Disable' : 'Enable' }}</button></form>
                </div>
            @endforeach
        </div>{{ $customers->links() }}
    </article>
@endsection
