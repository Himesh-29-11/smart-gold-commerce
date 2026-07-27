@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<section class="simple-auth"><div class="auth-card otp-card"><span class="otp-icon">↺</span><span class="kicker dark">Account recovery</span><h1>Reset your password</h1><p>Enter your registered email. We will queue a secure, time-limited reset link.</p><form method="POST" action="{{ route('password.email') }}">@csrf<label for="forgot-email">Email address<input id="forgot-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus @error('email') aria-invalid="true" @enderror></label>@error('email')<small class="field-error" role="alert">{{ $message }}</small>@enderror<button class="button button-lg full" type="submit">Email reset link</button></form><a class="text-link center" href="{{ route('login') }}">← Return to sign in</a><small>For your security, reset links expire and can be used only once.</small></div></section>
@endsection
