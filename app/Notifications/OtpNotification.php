<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Verify your N & H Trust account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use this one-time code to verify your account:')
            ->line($this->code)
            ->line('The code expires in 10 minutes. Never share it with anyone.')
            ->line('If you did not create this account, you can ignore this message.');
    }
}
