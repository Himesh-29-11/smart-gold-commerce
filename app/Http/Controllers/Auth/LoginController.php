<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\LoginNotification;
use App\Services\NotificationDeliveryService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, OtpService $otp, NotificationDeliveryService $notifications): RedirectResponse
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
        } $request->session()->regenerate();
        $user = $request->user();
        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account has been disabled.']);
        } $user->update(['last_login_at' => now()]);
        if (! $user->otp_verified_at) {
            $otp->issue($user);

            return redirect()->route('otp.show');
        }

        $notifications->send($user, new LoginNotification(
            ipAddress: $request->ip() ?? 'Unknown',
            userAgent: (string) $request->userAgent(),
            occurredAt: now(),
        ));

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('account.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
