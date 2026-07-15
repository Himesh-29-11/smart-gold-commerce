<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function ordersCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Customer', 'Status', 'Payment', 'Subtotal', 'Tax', 'Discount', 'Delivery', 'Total', 'Created']);
            Order::with('user')->orderBy('id')->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [$order->reference, $order->user->email, $order->status, $order->payment_status, $order->subtotal, $order->tax, $order->discount, $order->delivery_charge, $order->total, $order->created_at->toIso8601String()]);
                }
            });
            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
