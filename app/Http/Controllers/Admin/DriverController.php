<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(): View
    {
        return view('admin.drivers', [
            'drivers' => User::where('role', 'driver')->withCount('deliveryAssignments')->latest()->get(),
            'shipments' => Shipment::with(['order.user', 'assignment.driver'])
                ->whereHas('order', fn ($query) => $query->where('payment_status', 'paid'))
                ->latest()->limit(50)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => ['required', Password::min(10)->mixedCase()->numbers()],
        ]);
        User::create($data + [
            'role' => 'driver',
            'is_active' => true,
            'email_verified_at' => now(),
            'otp_verified_at' => now(),
        ]);

        return back()->with('success', 'Driver account created. Share the temporary password securely and require a change before production use.');
    }

    public function toggle(User $driver): RedirectResponse
    {
        abort_unless($driver->isDriver(), 404);
        $driver->update(['is_active' => ! $driver->is_active]);

        return back()->with('success', 'Driver access updated.');
    }

    public function assign(Request $request, Shipment $shipment, ShipmentService $shipments): RedirectResponse
    {
        $data = $request->validate(['driver_id' => 'required|exists:users,id']);
        $driver = User::where('role', 'driver')->findOrFail($data['driver_id']);
        $shipments->assignDriver($shipment, $driver, $request->user());

        return back()->with('success', 'Delivery assigned and the customer timeline was updated.');
    }
}
