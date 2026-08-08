<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ShowOtpCommand extends Command
{
    protected $signature = 'otp:show {email? : Optional user email address}';

    protected $description = 'Display the latest active OTP verification code for unverified users (useful for local testing)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $query = User::query()->whereNull('otp_verified_at');

        if ($email) {
            $query->where('email', 'like', "%{$email}%");
        }

        $users = $query->latest()->limit(10)->get();

        if ($users->isEmpty()) {
            $this->info('No pending OTP verification codes found. (All accounts are already verified!)');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Email Address', 'OTP Code', 'Status'],
            $users->map(fn (User $u) => [
                '#'.($u->customer_code ?? $u->id),
                $u->name,
                $u->email,
                $u->otp_code ?: 'None generated',
                'Pending OTP Verification',
            ])
        );

        return self::SUCCESS;
    }
}
