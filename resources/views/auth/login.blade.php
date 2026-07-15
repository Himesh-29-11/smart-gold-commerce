@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
    <section class="auth-shell">
        <div class="auth-art"><img src="{{ asset('images/products/gold-bar.jpg') }}" alt="Certified gold bar">
            <div><span class="kicker">Welcome back</span>
                <h1>Your gold journey,<br>continued securely.</h1>
                <p>Track purchases, saved products and financing requests from one private account.</p>
            </div>
        </div>
        <div class="auth-card"><a class="brand" href="{{ route('home') }}"><span
                    class="brand-mark">A</span><span><strong>AURUM</strong><small>TRUST</small></span></a>
            <h2>Sign in</h2>
            <p>Enter your account credentials.</p>
            <form method="POST" action="{{ route('login') }}">@csrf<label>Email address<input type="email" name="email"
                        value="{{ old('email') }}" autocomplete="email" required autofocus></label><label>Password<input
                        type="password" name="password" autocomplete="current-password" required></label><label
                    class="check"><input type="checkbox" name="remember"> Keep me signed in on this device</label><button
                    class="button button-lg full" type="submit">Sign in securely</button></form>
            <div class="auth-divider">New to N & H Trust?</div><a class="button button-outline full"
                href="{{ route('register') }}">Create an account</a>
            <p class="form-foot">Protected by rate limiting, secure sessions and CSRF controls.</p>
        </div>
    </section>
@endsection
