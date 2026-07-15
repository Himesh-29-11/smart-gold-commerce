@extends('layouts.app')
@section('title', 'Create account')
@section('content')
    <section class="auth-shell">
        <div class="auth-art"><img src="{{ asset('images/products/gold-necklace.jpg') }}" alt="Gold necklace">
            <div><span class="kicker">Join with confidence</span>
                <h1>Clarity from browsing<br>to delivery.</h1>
                <p>Save favourites, receive invoices and follow every order from your account.</p>
            </div>
        </div>
        <div class="auth-card"><a class="brand" href="{{ route('home') }}"><span
                    class="brand-mark">A</span><span><strong>AURUM</strong><small>TRUST</small></span></a>
            <h2>Create account</h2>
            <p>We’ll email a one-time code to verify you.</p>
            <form method="POST" action="{{ route('register') }}">@csrf<label>Full name<input name="name"
                        value="{{ old('name') }}" autocomplete="name" required></label><label>Email address<input
                        type="email" name="email" value="{{ old('email') }}" autocomplete="email"
                        required></label><label>Mobile number <small>(optional)</small><input type="tel" name="phone"
                        value="{{ old('phone') }}" autocomplete="tel" placeholder="+91"></label><label>Password<input
                        type="password" name="password" autocomplete="new-password" minlength="8" required><small>8+
                        characters with uppercase, lowercase and a number.</small></label><label>Confirm password<input
                        type="password" name="password_confirmation" autocomplete="new-password" required></label><label
                    class="check"><input type="checkbox" name="terms" value="1" required> I accept the terms,
                    privacy notice and risk disclosure.</label><button class="button button-lg full" type="submit">Create secure account</button></form>
            <div class="auth-divider">Already registered?</div><a class="text-link center" href="{{ route('login') }}">Sign
                in to your account →</a>
        </div>
    </section>
@endsection
