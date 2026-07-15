<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sales = Order::where('payment_status', 'paid')->where('created_at', '>=', now()->subDays(30))->selectRaw('DATE(created_at) day, SUM(total) total')->groupBy('day')->orderBy('day')->get();

        return view('admin.dashboard', ['stats' => ['revenue' => Order::where('payment_status', 'paid')->sum('total'), 'orders' => Order::count(), 'customers' => User::where('role', 'customer')->count(), 'loans' => LoanRequest::whereIn('status', ['submitted', 'under_review'])->count(), 'low_stock' => Product::where('stock_quantity', '<=', 3)->count()], 'sales' => $sales, 'recentOrders' => Order::with('user')->latest()->limit(8)->get()]);
    }
}
