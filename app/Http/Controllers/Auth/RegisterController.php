<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email:rfc,dns|max:190|unique:users', 'phone' => 'nullable|string|max:20|unique:users', 'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()], 'terms' => 'accepted']);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();
        $otp->issue($user);

        return redirect()->route('otp.show')->with('status', 'We sent a 6-digit verification code to your email.');
    }
}
