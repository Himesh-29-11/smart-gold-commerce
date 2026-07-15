<?php

namespace App\Notifications;

use App\Models\LoanRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LoanRequest $loan) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Financing request update · '.$this->loan->reference)
            ->greeting('Your request has an update')
            ->line('Status: '.str_replace('_', ' ', ucfirst($this->loan->status)).'.')
            ->line('Sign in to view request tracking information and any next steps from the selected provider.')
            ->action('View request', route('loans.index'))
            ->line('A status update is not a promise of approval or disbursal. Final terms are controlled by the selected independent provider.');
    }
}
