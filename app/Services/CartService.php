<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(private readonly GoldPriceService $prices) {}

    public function cartFor(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function add(User $user, Product $product, int $quantity = 1): void
    {
        if (! $product->is_active || $product->stock_quantity < 1) {
            throw ValidationException::withMessages([
                'product' => 'This product is currently unavailable.',
            ]);
        }

        $cart = $this->cartFor($user);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min(
            ($item->exists ? $item->quantity : 0) + $quantity,
            $product->stock_quantity,
            10,
        );
        $item->save();
    }

    public function quote(Cart $cart): array
    {
        $cart->load('items.product');
        $lines = [];
        $subtotal = 0.0;

        foreach ($cart->items as $item) {
            if (! $item->product || ! $item->product->is_active) {
                continue;
            }

            $unitPrice = $this->prices->productPrice($item->product);
            $lineSubtotal = round($unitPrice * $item->quantity, 2);
            $subtotal += $lineSubtotal;
            $lines[] = [
                'item' => $item,
                'product' => $item->product,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
                'tax' => 0.0,
                'total' => $lineSubtotal,
            ];
        }

        [$coupon, $discount] = $this->couponDiscount($cart->coupon_code, $subtotal);
        $discountRatio = $subtotal > 0 ? $discount / $subtotal : 0;
        $tax = 0.0;

        foreach ($lines as &$line) {
            $taxableValue = $line['subtotal'] * (1 - $discountRatio);
            $line['tax'] = round(
                $taxableValue * ((float) $line['product']->gst_percentage / 100),
                2,
            );
            $line['total'] = round($line['subtotal'] + $line['tax'], 2);
            $tax += $line['tax'];
        }
        unset($line);

        $delivery = ($subtotal - $discount) >= (float) config('commerce.free_delivery_threshold')
            || $subtotal <= 0
            ? 0.0
            : (float) config('commerce.delivery_charge');
        $total = round($subtotal - $discount + $tax + $delivery, 2);

        return compact('lines', 'subtotal', 'tax', 'discount', 'delivery', 'coupon', 'total');
    }

    /** @return array{0: Coupon|null, 1: float} */
    private function couponDiscount(?string $code, float $subtotal): array
    {
        if (! $code) {
            return [null, 0.0];
        }

        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon || ! $coupon->isAvailable($subtotal)) {
            return [null, 0.0];
        }

        $discount = $coupon->type === 'percent'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;
        if ($coupon->maximum_discount) {
            $discount = min($discount, (float) $coupon->maximum_discount);
        }

        return [$coupon, min(round($discount, 2), $subtotal)];
    }
}
