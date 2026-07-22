<?php

namespace App\Services;

use App\Jobs\SendNotificationMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function issue(User $user, string $purpose = 'registration'): void
    {
        OtpCode::where('user_id', $user->id)->where('purpose', $purpose)->whereNull('verified_at')->delete();
        $code = (string) random_int(100000, 999999);
        OtpCode::create(['user_id' => $user->id, 'purpose' => $purpose, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10)]);
        SendNotificationMail::dispatch($user, new OtpNotification($code));
    }

    public function verify(User $user, string $code, string $purpose = 'registration'): void
    {
        $otp = OtpCode::where('user_id', $user->id)->where('purpose', $purpose)->whereNull('verified_at')->latest()->first();
        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'This verification code has expired. Request a new one.']);
        }
        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Request a new code.']);
        }
        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'The verification code is incorrect.']);
        }
        $otp->update(['verified_at' => now()]);
        $user->update(['otp_verified_at' => now(), 'email_verified_at' => now()]);
    }
}
