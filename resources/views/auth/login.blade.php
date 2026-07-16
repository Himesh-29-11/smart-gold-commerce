@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
    <section class="auth-shell">
        <div class="auth-art">
            <img src="{{ asset('images/products/gold-bar.jpg') }}" alt="Certified gold bar">
            <div>
                <span class="kicker">Welcome back</span>
                <h1>Your gold journey,<br>continued securely.</h1>
                <p>Track purchases, saved products and financing requests from one private account.</p>
            </div>
        </div>

        <div class="auth-card">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">NH</span>
                <span><strong>N & H</strong><small>TRUST</small></span>
            </a>
            <h2>Sign in</h2>
            <p>Enter your account credentials.</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label for="login-email">
                    Email address
                    <input id="login-email" type="email" name="email" value="{{ old('email') }}"
                        autocomplete="email" required autofocus @error('email') aria-invalid="true" @enderror>
                    @error('email')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label for="login-password">
                    Password
                    <div class="password-control">
                        <input id="login-password" type="password" name="password" autocomplete="current-password" required
                            @error('password') aria-invalid="true" @enderror>
                        <button class="password-toggle" type="button" data-password-toggle
                            aria-controls="login-password" aria-pressed="false" aria-label="Show password"
                            title="Show password">
                            <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                <circle cx="12" cy="12" r="2.75" />
                            </svg>
                            <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="m3 3 18 18M10.6 6.2A10.5 10.5 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.2 2.8M6.2 6.2C3.8 7.7 2.5 12 2.5 12s3.5 6 9.5 6a9.6 9.6 0 0 0 3.2-.5M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label class="check">
                    <input type="checkbox" name="remember">
                    <span>Keep me signed in on this device</span>
                </label>

                <button class="button button-lg full" type="submit">Sign in securely</button>
            </form>

            <div class="auth-divider">New to N & H Trust?</div>
            <a class="button button-outline full" href="{{ route('register') }}">Create an account</a>
            <p class="form-foot">Protected by rate limiting, secure sessions and CSRF controls.</p>
        </div>
    </section>
@endsection
