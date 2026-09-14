<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->role === 'vendor'
            ? 'vendor.dashboard'
            : 'dashboard');
    }

    return app(AuthController::class)->showLogin();
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/vendor-dashboard', [AuthController::class, 'vendorDashboard'])->name('vendor.dashboard');
    Route::post('/vendor-payments', [AuthController::class, 'storeVendorPayment'])->name('vendor.payments.store');
    Route::get('/vendors', [AuthController::class, 'vendors'])->name('vendors.index');
    Route::get('/vendors/create', [AuthController::class, 'createVendor'])->name('vendors.create');
    Route::get('/stalls', [AuthController::class, 'stalls'])->name('stalls');
    Route::get('/rentals', [AuthController::class, 'rentals'])->name('rentals');
    Route::post('/vendors', [AuthController::class, 'storeVendor'])->name('vendors.store');
    Route::get('/payments', [AuthController::class, 'payments'])->name('payments');
    Route::get('/payments/create', [AuthController::class, 'createPayment'])->name('payments.create');
    Route::post('/payments', [AuthController::class, 'storePayment'])->name('payments.store');
    Route::get('/due-dates', [AuthController::class, 'dueDates'])->name('due-dates');
    Route::get('/reports', [AuthController::class, 'reports'])->name('reports');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
