@extends('layouts.app')
@section('title', 'Create account')
@section('content')
    <section class="auth-shell">
        <div class="auth-art">
            <img src="{{ asset('images/products/gold-necklace.jpg') }}" alt="Gold necklace">
            <div>
                <span class="kicker">Join with confidence</span>
                <h1>Clarity from browsing<br>to delivery.</h1>
                <p>Save favourites, receive invoices and follow every order from your account.</p>
            </div>
        </div>

        <div class="auth-card">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">NH</span>
                <span><strong>N & H</strong><small>TRUST</small></span>
            </a>
            <h2>Create account</h2>
            <p>We’ll email a one-time code to verify you.</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <label for="register-name">
                    Full name
                    <input id="register-name" name="name" value="{{ old('name') }}" autocomplete="name" required
                        @error('name') aria-invalid="true" @enderror>
                    @error('name')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label for="register-email">
                    Email address
                    <input id="register-email" type="email" name="email" value="{{ old('email') }}"
                        autocomplete="email" required @error('email') aria-invalid="true" @enderror>
                    @error('email')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label for="register-phone">
                    Mobile number <small>(optional)</small>
                    <input id="register-phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                        inputmode="tel" placeholder="+91" @error('phone') aria-invalid="true" @enderror>
                    @error('phone')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label for="register-password">
                    Password
                    <div class="password-control">
                        <input id="register-password" type="password" name="password" autocomplete="new-password"
                            minlength="8" required @error('password') aria-invalid="true" @enderror>
                        <button class="password-toggle" type="button" data-password-toggle
                            aria-controls="register-password" aria-pressed="false" aria-label="Show password"
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
                    <small class="field-hint">Use 8+ characters with uppercase, lowercase and a number.</small>
                    @error('password')
                        <small class="field-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>

                <label for="register-password-confirmation">
                    Confirm password
                    <div class="password-control">
                        <input id="register-password-confirmation" type="password" name="password_confirmation"
                            autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle
                            aria-controls="register-password-confirmation" aria-pressed="false"
                            aria-label="Show password confirmation" title="Show password confirmation">
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
                </label>

                <label class="check">
                    <input type="checkbox" name="terms" value="1" required>
                    <span>I accept the terms, privacy notice and risk disclosure.</span>
                </label>

                <button class="button button-lg full" type="submit">Create secure account</button>
            </form>

            <div class="auth-divider">Already registered?</div>
            <a class="text-link center" href="{{ route('login') }}">Sign in to your account →</a>
        </div>
    </section>
@endsection
