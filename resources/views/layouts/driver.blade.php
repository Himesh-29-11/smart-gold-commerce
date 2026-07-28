<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Driver') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="driver-body">
    <a class="skip-link" href="#driver-main-content">Skip to deliveries</a>
    <header class="driver-header">
        <a href="{{ route('driver.dashboard') }}">
            <span>NH</span>
            <div>
                <b>N & H Delivery</b>
                <small>Secure driver portal</small>
            </div>
        </a>
        <div>
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="driver-main" id="driver-main-content">
        @if (session('success'))
            <div class="driver-alert">✓ {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="driver-alert error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
