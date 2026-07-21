<?php

namespace App\Services;

use App\Jobs\SendNotificationMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationDeliveryService
{
    /**
     * Persist the in-app notification synchronously, then queue email so an
     * unavailable SMTP server can never block login or an operational update.
     */
    public function send(User $user, Notification $notification): void
    {
        $user->notifyNow($notification, ['database']);

        try {
            SendNotificationMail::dispatch($user, $notification);
        } catch (\Throwable $exception) {
            $this->reportQueueFailure($user, $notification::class, $exception);
        }
    }

    public function sendMailable(User $user, Notification $notification, Mailable $mailable): void
    {
        $user->notifyNow($notification, ['database']);

        try {
            Mail::to($user->email)->queue($mailable);
        } catch (\Throwable $exception) {
            $this->reportQueueFailure($user, $mailable::class, $exception);
        }
    }

    private function reportQueueFailure(User $user, string $messageClass, \Throwable $exception): void
    {
        report($exception);
        Log::warning('Email could not be queued.', [
            'user_id' => $user->id,
            'message' => $messageClass,
            'error' => $exception->getMessage(),
        ]);
    }
}
