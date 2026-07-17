<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\GoldPriceService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(GoldPriceService $goldPrices): View
    {
        $sales = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('DATE(created_at) day, SUM(total) total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
        $rates = $goldPrices->latestRates();

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => Order::where('payment_status', 'paid')->sum('total'),
                'revenue_30d' => Order::where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->subDays(30))->sum('total'),
                'orders' => Order::count(),
                'pending_orders' => Order::whereIn('status', ['pending', 'confirmed', 'processing'])->count(),
                'customers' => User::where('role', 'customer')->count(),
                'active_customers' => User::where('role', 'customer')->where('is_active', true)->count(),
                'loans' => LoanRequest::whereIn('status', ['submitted', 'under_review', 'documents_required'])->count(),
                'low_stock' => Product::where('is_active', true)->where('stock_quantity', '<=', 3)->count(),
            ],
            'sales' => $sales,
            'rates' => $rates,
            'goldDataMode' => $goldPrices->dataMode(),
            'goldSource' => $goldPrices->activeSource(),
            'recentOrders' => Order::with('user')->latest()->limit(8)->get(),
        ]);
    }
}
