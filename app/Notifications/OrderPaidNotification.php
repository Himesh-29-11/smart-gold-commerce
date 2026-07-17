<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'order',
            'title' => 'Payment confirmed',
            'message' => 'Payment for '.$this->order->reference.' was verified.',
            'status' => 'paid',
            'reference' => $this->order->reference,
            'url' => route('orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items', 'shipment']);
        $message = (new MailMessage)
            ->subject('Payment confirmed · '.$order->reference)
            ->greeting('Payment confirmed')
            ->line('We verified your payment of ₹'.number_format((float) $order->total, 2).' for order '.$order->reference.'.');

        foreach ($order->items as $item) {
            $message->line($item->quantity.' × '.data_get($item->product_snapshot, 'name').' — ₹'.number_format((float) $item->line_total, 2));
        }

        if ($order->shipment) {
            $message->line('Tracking ID: '.$order->shipment->tracking_number);
        }

        return $message
            ->line('Your certified gold order is now in the secure fulfilment flow.')
            ->action('View order', route('orders.show', $order))
            ->line('We will never ask you to share an OTP or payment credential by email.');
    }
}
