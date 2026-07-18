<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationDeliveryService
{
    /**
     * Persist the in-app notification first. Email failures are reported but
     * never roll back the operational status change or database notification.
     */
    public function send(User $user, Notification $notification): void
    {
        $user->notifyNow($notification, ['database']);

        try {
            $user->notifyNow($notification, ['mail']);
        } catch (\Throwable $exception) {
            report($exception);
            Log::warning('Notification email delivery failed.', [
                'user_id' => $user->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
