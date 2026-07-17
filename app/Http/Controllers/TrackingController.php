<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function show(Request $request, Order $order, ShipmentService $shipments): View
    {
        $this->authorizeOrder($request, $order);
        $shipment = $order->shipment;
        if (! $shipment && $order->payment_status === 'paid') {
            $shipment = $shipments->ensureForOrder($order);
        }

        return view('account.tracking', [
            'order' => $order->load('items'),
            'shipment' => $shipment?->load('events'),
            'googleMapsKey' => config('services.google_maps.key'),
        ]);
    }

    public function data(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        $shipment = $order->shipment?->load('events');

        return response()->json([
            'order' => $order->reference,
            'shipment' => $shipment ? [
                'tracking_number' => $shipment->tracking_number,
                'carrier' => $shipment->carrier,
                'carrier_tracking_number' => $shipment->carrier_tracking_number,
                'status' => $shipment->status,
                'estimated_delivery_at' => $shipment->estimated_delivery_at?->toIso8601String(),
                'location' => $shipment->hasLocation() ? [
                    'latitude' => round((float) $shipment->current_latitude, 3),
                    'longitude' => round((float) $shipment->current_longitude, 3),
                    'updated_at' => $shipment->location_updated_at?->toIso8601String(),
                    'precision' => 'approximate',
                ] : null,
                'events' => $shipment->events->map(fn ($event) => [
                    'status' => $event->status,
                    'title' => $event->title,
                    'description' => $event->description,
                    'occurred_at' => $event->occurred_at->toIso8601String(),
                ]),
            ] : null,
        ])->header('Cache-Control', 'no-store');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
