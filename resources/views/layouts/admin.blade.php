<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Operations') · {{ config('app.name') }}</title>
    <meta name="robots" content="noindex,nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="admin-body">
    <div class="admin-app">
        <aside class="admin-sidebar" data-admin-sidebar>
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span>NH</span>
                <div><strong>N & H Trust</strong><small>Operations console</small></div>
            </a>

            <nav class="admin-navigation" aria-label="Administration">
                <span class="admin-nav-label">Workspace</span>
                <a @class(['active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}">
                    <i aria-hidden="true">⌂</i><span>Overview</span>
                </a>
                <a @class(['active' => request()->routeIs('admin.products.*')]) href="{{ route('admin.products.index') }}">
                    <i aria-hidden="true">◇</i><span>Products</span>
                </a>
                <a @class(['active' => request()->routeIs('admin.gold-prices.*')]) href="{{ route('admin.gold-prices.index') }}">
                    <i aria-hidden="true">◉</i><span>Gold-rate feed</span>
                </a>
                <a @class(['active' => request()->routeIs('admin.orders.*')]) href="{{ route('admin.orders.index') }}">
                    <i aria-hidden="true">▤</i><span>Orders</span>
                </a>
                <a @class(['active' => request()->routeIs('admin.customers.*')]) href="{{ route('admin.customers.index') }}">
                    <i aria-hidden="true">♙</i><span>Customers</span>
                </a>
                <a @class(['active' => request()->routeIs('admin.loans.*')]) href="{{ route('admin.loans.index') }}">
                    <i aria-hidden="true">₹</i><span>Loan requests</span>
                </a>
                <a @class(['active' => request()->routeIs('admin.drivers.*')]) href="{{ route('admin.drivers.index') }}">
                    <i aria-hidden="true">🛵</i><span>Delivery team</span>
                </a>

                <span class="admin-nav-label">Reports</span>
                <a href="{{ route('admin.reports.orders') }}">
                    <i aria-hidden="true">↓</i><span>Export orders</span>
                </a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener">
                    <i aria-hidden="true">↗</i><span>View storefront</span>
                </a>
            </nav>

            <div class="admin-user">
                <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></div>
            </div>
        </aside>
        <button class="admin-sidebar-backdrop" type="button" aria-label="Close navigation" data-admin-nav-close></button>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <button class="admin-menu-toggle" type="button" aria-label="Open navigation" data-admin-nav-toggle>☰</button>
                <div><strong>Operations</strong><span>{{ now()->format('D, d M Y') }}</span></div>
                <div class="admin-top-actions">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener">Storefront ↗</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
                </div>
            </header>

            <main class="admin-main">
                @if (session('success'))
                    <div class="admin-alert success" role="status">✓ {{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="admin-alert error" role="alert">
                        <strong>Please review the following:</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('admin-content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
