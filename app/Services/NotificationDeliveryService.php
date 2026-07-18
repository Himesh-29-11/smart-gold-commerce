<?php

namespace App\Services;

use App\Jobs\SendNotificationMail;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
            report($exception);
            Log::warning('Notification email could not be queued.', [
                'user_id' => $user->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
