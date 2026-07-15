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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Payment confirmed · '.$this->order->reference)->greeting('Payment confirmed')->line('We verified your payment of ₹'.number_format((float) $this->order->total, 2).' for order '.$this->order->reference.'.')->line('Your certified gold order is now in the secure fulfilment flow.')->action('View order', route('orders.show', $this->order))->line('We will never ask you to share an OTP or payment credential by email.');
    }
}
