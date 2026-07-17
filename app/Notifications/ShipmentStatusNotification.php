<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Shipment $shipment, private readonly string $eventTitle) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'shipment',
            'title' => $this->eventTitle,
            'message' => 'Tracking '.$this->shipment->tracking_number.' is '.str_replace('_', ' ', $this->shipment->status).'.',
            'status' => $this->shipment->status,
            'reference' => $this->shipment->tracking_number,
            'url' => route('orders.tracking', $this->shipment->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shipment = $this->shipment->loadMissing('order');

        $message = (new MailMessage)
            ->subject($this->eventTitle.' · '.$shipment->tracking_number)
            ->greeting('Delivery update')
            ->line('Order: '.$shipment->order->reference)
            ->line('Tracking ID: '.$shipment->tracking_number)
            ->line('Current status: '.str_replace('_', ' ', ucfirst($shipment->status)).'.');

        if ($shipment->estimated_delivery_at) {
            $message->line('Estimated delivery: '.$shipment->estimated_delivery_at->format('d M Y, h:i A').' IST');
        }

        return $message
            ->action('Track your order', route('orders.tracking', $shipment->order))
            ->line('Exact location is shown only when an approved courier supplies a recent location update.');
    }
}
