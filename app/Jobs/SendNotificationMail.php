<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 20;

    public function __construct(
        public readonly User $user,
        public readonly Notification $notification,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $this->user->notifyNow($this->notification, ['mail']);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Queued notification email failed.', [
            'user_id' => $this->user->id,
            'notification' => $this->notification::class,
            'error' => $exception?->getMessage(),
        ]);
    }
}
