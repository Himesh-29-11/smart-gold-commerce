<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly GoldPriceService $prices,
    ) {}

    public function create(User $user, array $address, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($user, $address, $notes) {
            $cart = $this->carts->cartFor($user);
            $cartItems = $cart->items()->lockForUpdate()->get();
            $lockedProducts = Product::whereIn('id', $cartItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lockedProducts->each(fn (Product $product) => $this->prices->assertCheckoutAvailable($product));
            $quote = $this->carts->quote($cart);
            if (! $quote['lines']) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }
            foreach ($quote['lines'] as $line) {
                $product = $lockedProducts->get($line['product']->id);
                if (! $product || ! $product->is_active || $product->stock_quantity < $line['item']->quantity) {
                    throw ValidationException::withMessages(['cart' => $line['product']->name.' no longer has enough stock.']);
                }
            }
            $order = Order::create(['user_id' => $user->id, 'reference' => config('commerce.invoice_prefix').'-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)), 'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'], 'tax' => $quote['tax'], 'delivery_charge' => $quote['delivery'], 'total' => $quote['total'], 'coupon_code' => $quote['coupon']?->code, 'shipping_address' => $address, 'notes' => $notes]);
            foreach ($quote['lines'] as $line) {
                $p = $lockedProducts->get($line['product']->id);
                $order->items()->create(['product_id' => $p->id, 'product_snapshot' => ['name' => $p->name, 'sku' => $p->sku, 'purity' => $p->purity, 'weight_grams' => $p->weight_grams, 'certification' => $p->certification, 'image_url' => $p->image_url], 'quantity' => $line['item']->quantity, 'unit_price' => $line['unit_price'], 'tax_amount' => $line['tax'], 'line_total' => $line['total']]);
                $p->decrement('stock_quantity', $line['item']->quantity);
            }
            $cart->items()->delete();
            $cart->update(['coupon_code' => null]);

            return $order->load('items');
        });
    }
}
