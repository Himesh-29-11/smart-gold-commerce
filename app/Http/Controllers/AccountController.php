<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        return view('account.dashboard', ['orders' => $request->user()->orders()->with('items')->latest()->limit(5)->get(), 'loans' => $request->user()->loanRequests()->with('partner')->latest()->limit(5)->get()]);
    }

    public function orders(Request $request): View
    {
        return view('account.orders', ['orders' => $request->user()->orders()->with('items')->latest()->paginate(10)]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return view('account.order-show', ['order' => $order->load(['items', 'payments'])]);
    }

    public function invoice(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($order->payment_status === 'paid', 404);

        return view('account.invoice', ['order' => $order->load(['user', 'items'])]);
    }
}
