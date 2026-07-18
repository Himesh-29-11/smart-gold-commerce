<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LoginNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $ipAddress,
        private readonly string $userAgent,
        private readonly CarbonInterface $occurredAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'security',
            'title' => 'New sign-in',
            'message' => 'Your account was accessed from '.$this->ipAddress.' on '.$this->occurredAt->format('d M Y, h:i A').' IST.',
            'status' => 'login',
            'reference' => null,
            'url' => $notifiable->isAdmin() ? route('admin.dashboard') : route('account.dashboard'),
            'ip_address' => $this->ipAddress,
            'device' => Str::limit($this->userAgent, 180),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New sign-in to your N & H Trust account')
            ->greeting('New account sign-in')
            ->line('Time: '.$this->occurredAt->format('d M Y, h:i A').' IST')
            ->line('IP address: '.$this->ipAddress)
            ->line('Device: '.Str::limit($this->userAgent, 180))
            ->line('If this was you, no action is required. If not, change your password and contact support immediately.');
    }
}
