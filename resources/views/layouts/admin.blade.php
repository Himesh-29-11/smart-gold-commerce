@extends('layouts.app')
@section('content')
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div><span class="eyebrow">Operations console</span>
                <h2>Commerce admin</h2>
            </div>
            <nav><a @class(['active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}">⌂ Overview</a><a
                    @class(['active' => request()->routeIs('admin.products.*')]) href="{{ route('admin.products.index') }}">◇ Products</a><a
                    @class(['active' => request()->routeIs('admin.orders.*')]) href="{{ route('admin.orders.index') }}">▤ Orders</a><a
                    @class(['active' => request()->routeIs('admin.customers.*')]) href="{{ route('admin.customers.index') }}">♙ Customers</a><a
                    @class(['active' => request()->routeIs('admin.loans.*')]) href="{{ route('admin.loans.index') }}">₹ Loan requests</a><a
                    href="{{ route('admin.reports.orders') }}">↓ Export orders</a></nav>
            <div class="admin-user"><span>{{ substr(auth()->user()->name, 0, 1) }}</span>
                <div><b>{{ auth()->user()->name }}</b><small>Administrator</small></div>
            </div>
        </aside>
        <section class="admin-main">@yield('admin-content')</section>
    </div>
@endsection
