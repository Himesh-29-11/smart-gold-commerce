<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->otp_verified_at) {
            return redirect()->route('account.dashboard');
        }

return view('auth.verify-otp');
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $otp->verify($request->user(), $data['code']);

        return redirect()->route('account.dashboard')->with('success', 'Your account is verified. Welcome!');
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $otp->issue($request->user());

        return back()->with('status', 'A new verification code has been sent.');
    }
}
