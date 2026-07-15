<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, CartService $service): View
    {
        $cart = $service->cartFor($request->user());

        return view('cart.index', ['cart' => $cart, 'quote' => $service->quote($cart)]);
    }

    public function store(Request $request, Product $product, CartService $service): RedirectResponse
    {
        $data = $request->validate(['quantity' => 'nullable|integer|min:1|max:10']);
        $service->add($request->user(), $product, (int) ($data['quantity'] ?? 1));

        return back()->with('success', 'Added to your cart.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart->user_id === $request->user()->id, 403);
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:10']);
        if ($data['quantity'] > $item->product->stock_quantity) {
            return back()->withErrors(['quantity' => 'Only '.$item->product->stock_quantity.' units are available.']);
        } $item->update($data);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart->user_id === $request->user()->id, 403);
        $item->delete();

        return back()->with('success', 'Item removed.');
    }

    public function applyCoupon(Request $request, CartService $service): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:30']);
        $cart = $service->cartFor($request->user());
        $coupon = Coupon::where('code', strtoupper($data['code']))->first();
        $subtotal = $service->quote($cart)['subtotal'];
        if (! $coupon || ! $coupon->isAvailable($subtotal)) {
            return back()->withErrors(['code' => 'This coupon is invalid or does not apply to your cart.']);
        } $cart->update(['coupon_code' => $coupon->code]);

        return back()->with('success', 'Coupon applied.');
    }

    public function removeCoupon(Request $request, CartService $service): RedirectResponse
    {
        $service->cartFor($request->user())->update(['coupon_code' => null]);

        return back()->with('success','Coupon removed.');
    }
}
