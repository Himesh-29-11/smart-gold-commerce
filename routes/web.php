<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\GoldPriceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/gold', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/gold/{product:slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/gold-prices', GoldPriceController::class)->name('gold-prices');
Route::get('/gold-loan-assistance', [LoanController::class, 'index'])->name('loans.index');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/verify-account', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/verify-account', [OtpController::class, 'verify'])->middleware('throttle:6,1')->name('otp.verify');
    Route::post('/verify-account/resend', [OtpController::class, 'resend'])->middleware('throttle:1,1')->name('otp.resend');
});

Route::middleware(['auth', 'active', 'otp'])->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('orders.index');
    Route::get('/account/orders/{order}', [AccountController::class, 'show'])->name('orders.show');
    Route::get('/account/orders/{order}/invoice', [AccountController::class, 'invoice'])->name('orders.invoice');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/gold/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
    Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
    Route::post('/gold-loan-assistance', [LoanController::class, 'store'])->middleware('throttle:3,60')->name('loans.store');
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::post('/orders/{order}/payment', [CheckoutController::class, 'retry'])->name('payments.retry');
    Route::post('/payments/razorpay/return', [CheckoutController::class, 'razorpayReturn'])->name('payments.razorpay.return');
    Route::get('/payments/stripe/return', [CheckoutController::class, 'stripeReturn'])->name('payments.stripe.return');
});
Route::post('/payments/webhook/{provider}', [CheckoutController::class, 'webhook'])->middleware('throttle:120,1')->name('payments.webhook');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'otp', 'admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::resource('products', AdminProductController::class)->except('show');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::get('/loans', [AdminLoanController::class, 'index'])->name('loans.index');
    Route::patch('/loans/{loan}', [AdminLoanController::class, 'update'])->name('loans.update');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::patch('/customers/{user}/toggle', [AdminCustomerController::class, 'toggle'])->name('customers.toggle');
    Route::get('/reports/orders.csv',[AdminReportController::class, 'ordersCsv'])->name('reports.orders');
});
