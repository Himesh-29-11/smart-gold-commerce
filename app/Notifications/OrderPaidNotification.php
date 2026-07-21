<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPaidNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'order',
            'title' => 'Payment confirmed',
            'message' => 'Payment for '.$this->order->reference.' was verified. Your invoice email is being prepared.',
            'status' => 'paid',
            'reference' => $this->order->reference,
            'url' => route('orders.show', $this->order),
        ];
    }
}
