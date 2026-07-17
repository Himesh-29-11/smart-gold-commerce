<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'payment_review',
        'cancelled',
    ];

    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'payment' => 'nullable|in:paid,unpaid',
        ]);
        $query = Order::with('user');

        if ($search = $data['q'] ?? null) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'));
            });
        }
        if ($status = $data['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($payment = $data['payment'] ?? null) {
            $query->where('payment_status', $payment);
        }

        return view('admin.orders', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Order $order, ShipmentService $shipments): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        $statusChanged = false;
        $updatedOrder = DB::transaction(function () use ($order, $data, &$statusChanged): Order {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            $statusChanged = $lockedOrder->status !== $data['status'];

            if ($lockedOrder->payment_status === 'paid' && $data['status'] === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => 'Paid orders require a verified refund workflow before cancellation.',
                ]);
            }

            if ($data['status'] === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                $items = $lockedOrder->items()->get();
                $products = Product::whereIn('id', $items->pluck('product_id')->filter())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $products->get($item->product_id)?->increment('stock_quantity', $item->quantity);
                }
            }

            $lockedOrder->update($data);

            return $lockedOrder;
        });

        if ($statusChanged) {
            $shipments->syncFromOrderStatus($updatedOrder);
        }

        return back()->with('success', 'Order status updated and the customer was notified.');
    }
}
