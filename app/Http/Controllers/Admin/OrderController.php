<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
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
        $request->validate(['status' => 'nullable|in:'.implode(',', self::STATUSES)]);
        $query = Order::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.orders', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        DB::transaction(function () use ($order, $data): void {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

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
        });

        return back()->with('success', 'Order status updated.');
    }
}
