@extends('layouts.app')
@section('title', 'Verify account')
@section('content')
    <section class="simple-auth">
        <div class="auth-card otp-card"><span class="otp-icon">✉</span><span class="kicker dark">Account verification</span>
            <h1>Check your email</h1>
            <p>Enter the 6-digit code sent to <strong>{{ auth()->user()->email }}</strong>. It expires in 10 minutes.</p>
            <form method="POST" action="{{ route('otp.verify') }}">@csrf<label>Verification code<input class="otp-input"
                        name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                        placeholder="000000" required autofocus></label><button class="button button-lg full"
                    type="submit">Verify account</button></form>
            <form method="POST" action="{{ route('otp.resend') }}">@csrf<button class="nav-link text-link center"
                    type="submit">Send a new code</button></form><small>Never share a one-time code with anyone, including
                support staff.</small>
        </div>
    </section>
@endsection
