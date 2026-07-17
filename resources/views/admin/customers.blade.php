@extends('layouts.admin')
@section('title', 'Customers')
@section('admin-content')
    <div class="admin-heading"><div><span class="kicker dark">Customer operations</span><h1>Customers</h1><p>Review account activity and access without exposing passwords or payment credentials.</p></div></div>

    <section class="admin-panel">
        <form class="admin-search" method="GET" action="{{ route('admin.customers.index') }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email or phone">
            <select name="verification" aria-label="Verification"><option value="">All verification</option><option value="verified" @selected(request('verification') === 'verified')>Verified</option><option value="pending" @selected(request('verification') === 'pending')>Pending</option></select>
            <select name="access" aria-label="Account access"><option value="">All access</option><option value="active" @selected(request('access') === 'active')>Active</option><option value="disabled" @selected(request('access') === 'disabled')>Disabled</option></select>
            <button class="button button-outline" type="submit">Filter</button><a class="admin-filter-clear" href="{{ route('admin.customers.index') }}">Clear</a>
        </form>

        <div class="admin-table customer-table">
            <div class="table-row table-head"><span>Customer</span><span>Verified</span><span>Orders</span><span>Loans</span><span>Joined</span><span>Access</span></div>
            @forelse ($customers as $customer)
                <div class="table-row">
                    <span><b>{{ $customer->name }}</b><small>{{ $customer->email }} · {{ $customer->phone ?: 'No phone' }}</small></span>
                    <span class="status {{ $customer->otp_verified_at ? 'status-confirmed' : 'status-pending' }}">{{ $customer->otp_verified_at ? 'Verified' : 'Pending' }}</span>
                    <span><b>{{ $customer->orders_count }}</b></span><span><b>{{ $customer->loan_requests_count }}</b></span><span><b>{{ $customer->created_at->format('d M Y') }}</b></span>
                    <form method="POST" action="{{ route('admin.customers.toggle', $customer) }}" onsubmit="return confirm('{{ $customer->is_active ? 'Disable' : 'Enable' }} this customer account?')">@csrf @method('PATCH')<button class="{{ $customer->is_active ? 'danger-link' : 'success-link' }}" type="submit">{{ $customer->is_active ? 'Disable' : 'Enable' }}</button></form>
                </div>
            @empty
                <div class="admin-empty"><strong>No customers found</strong><span>There are no customer accounts matching these filters.</span></div>
            @endforelse
        </div>
        @include('admin.partials.pagination', ['paginator' => $customers])
    </section>
@endsection
