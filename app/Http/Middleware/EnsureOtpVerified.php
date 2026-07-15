<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->otp_verified_at) {
            return redirect()->route('otp.show')->with('status', 'Verify your account to continue.');
        }

        return $next($request);
    }
}
