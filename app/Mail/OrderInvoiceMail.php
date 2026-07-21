<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class OrderInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(public Order $order)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase confirmed · '.$this->order->reference,
        );
    }

    public function content(): Content
    {
        $order = $this->preparedOrder();

        return new Content(
            view: 'emails.orders.invoice',
            with: [
                'order' => $order,
                'productImages' => $this->productImages($order),
            ],
        );
    }

    public function attachments(): array
    {
        $order = $this->preparedOrder();

        return [
            Attachment::fromData(
                fn () => Pdf::loadView('emails.orders.invoice-pdf', ['order' => $order])
                    ->setPaper('a4')
                    ->output(),
                'invoice-'.$order->reference.'.pdf',
            )->withMime('application/pdf'),
        ];
    }

    private function preparedOrder(): Order
    {
        return $this->order->loadMissing(['user', 'items', 'shipment']);
    }

    private function productImages(Order $order): array
    {
        return $order->items->mapWithKeys(function ($item): array {
            $url = (string) data_get($item->product_snapshot, 'image_url', '');
            $path = null;

            if (str_starts_with($url, '/storage/')) {
                $path = storage_path('app/public/'.Str::after($url, '/storage/'));
            } elseif (str_starts_with($url, '/')) {
                $path = public_path(ltrim($url, '/'));
            }

            return [$item->id => [
                'path' => $path && is_file($path) ? $path : null,
                'url' => filter_var($url, FILTER_VALIDATE_URL) ? $url : null,
            ]];
        })->all();
    }
}
