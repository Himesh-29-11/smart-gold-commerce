<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAssignment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function dashboard(Request $request): View
    {
        return view('driver.dashboard', [
            'assignments' => $request->user()->deliveryAssignments()
                ->with(['shipment.order.user'])
                ->latest('assigned_at')->get(),
        ]);
    }

    public function show(Request $request, DeliveryAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);

        return view('driver.delivery', [
            'assignment' => $assignment->load(['shipment.order.user', 'shipment.events']),
        ]);
    }

    public function accept(Request $request, DeliveryAssignment $assignment, ShipmentService $shipments): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);
        abort_unless($assignment->status === 'assigned', 422);
        $shipments->updateAssignmentStatus($assignment, 'accepted');

        return back()->with('success', 'Delivery accepted. Start location sharing when the insured package is in your possession.');
    }

    public function start(Request $request, DeliveryAssignment $assignment, ShipmentService $shipments): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);
        abort_unless(in_array($assignment->status, ['accepted', 'active'], true), 422);
        $shipments->updateAssignmentStatus($assignment, 'active');

        return back()->with('success', 'Delivery started. Grant location permission and keep this page active.');
    }

    public function location(Request $request, DeliveryAssignment $assignment, ShipmentService $shipments): JsonResponse
    {
        $this->authorizeAssignment($request, $assignment);
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:100',
        ]);
        $location = $shipments->recordDriverLocation($assignment, $request->user(), $data);

        return response()->json([
            'recorded_at' => $location->recorded_at->toIso8601String(),
            'status' => 'out_for_delivery',
        ]);
    }

    public function complete(Request $request, DeliveryAssignment $assignment, ShipmentService $shipments): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);
        abort_unless($assignment->status === 'active', 422);
        $shipments->updateAssignmentStatus($assignment, 'completed');

        return redirect()->route('driver.dashboard')->with('success', 'Delivery completed and the customer was notified.');
    }

    private function authorizeAssignment(Request $request, DeliveryAssignment $assignment): void
    {
        abort_unless($assignment->driver_id === $request->user()->id, 403);
    }
}
