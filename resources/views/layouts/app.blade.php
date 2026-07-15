<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Certified Gold, Clearly Priced') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Discover certified gold with transparent pricing, secure checkout and verified financing assistance.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body>
    <div class="trust-strip"><span>✦ BIS certification details</span><span>↻ Market-linked prices</span><span>⌁ Secure
            payments</span><span>✓ Verified partners only</span></div>
    <header class="site-header">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ config('app.name') }} home"><span
                class="brand-mark">NH</span><span><strong>N & H</strong><small>TRUST</small></span></a>
        <button class="nav-toggle" type="button" aria-label="Toggle navigation" data-nav-toggle>☰</button>
        <nav class="main-nav" data-nav>
            <a href="{{ route('catalog.index') }}" @class(['active' => request()->routeIs('catalog.*')])>Shop gold</a>
            <a href="{{ route('gold-prices') }}" @class(['active' => request()->routeIs('gold-prices')])>Live rates</a>
            <a href="{{ route('loans.index') }}" @class(['active' => request()->routeIs('loans.*')])>Gold financing</a>
            @auth
                <a href="{{ route('wishlist.index') }}">♡ Wishlist</a>
                <a href="{{ route('cart.index') }}">Bag <span
                        class="nav-count">{{ auth()->user()->cart?->items()->sum('quantity') ?? 0 }}</span></a>
                @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}">Admin</a>@else<a
                        href="{{ route('account.dashboard') }}">Account</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link" type="submit">Sign
                        out</button></form>
            @else
                <a href="{{ route('login') }}">Sign in</a><a class="button button-sm"
                    href="{{ route('register') }}">Create account</a>
            @endauth
        </nav>
    </header>
    <main>
        @if (session('success'))
            <div class="flash flash-success" role="status">✓ {{ session('success') }}</div>
        @endif
        @if (session('status'))
            <div class="flash flash-info" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash flash-error" role="alert"><strong>Please check the following:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer">
        <div><a class="brand brand-light" href="{{ route('home') }}"><span class="brand-mark">NH</span><span><strong>N
                        & H</strong><small>TRUST</small></span></a>
            <p>A transparent commerce platform for certified gold from verified partners.</p>
        </div>
        <div>
            <strong>Explore</strong>
            <a href="{{ route('catalog.index') }}">Gold collection</a>
            <a href="{{ route('gold-prices') }}">Market dashboard</a>
            <a href="{{ route('loans.index') }}">Financing
                assistance</a>
        </div>
        <div>
            <strong>Trust & support</strong>
            <span>Secure payment processing</span>
            <span>Partner due diligence</span>
            <a href="mailto:{{ config('commerce.support_email') }}">{{ config('commerce.support_email') }}</a>
        </div>
        <div class="footer-disclaimer">
            <strong>Important</strong>
            <p>Rates marked “demo” are not live market quotes. Financing is provided by independent regulated lenders,
                subject to their assessment. N & H Trust does not issue loans.</p>
        </div>
        <div class="footer-bottom">
            © {{ date('Y') }} {{ config('app.name') }}. Demo brand names and data must be
            replaced before production. Gold prices can rise or fall.
        </div>
    </footer>
    @stack('scripts')
</body>

</html>
