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

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/vendors', [AuthController::class, 'vendors'])->name('vendors.index');
    Route::get('/vendors/create', [AuthController::class, 'createVendor'])->name('vendors.create');
    Route::get('/vendors/{vendor}', [AuthController::class, 'showVendor'])->name('vendors.show');
    Route::get('/vendors/{vendor}/edit', [AuthController::class, 'editVendor'])->name('vendors.edit');
    Route::get('/stalls', [AuthController::class, 'stalls'])->name('stalls');
    Route::post('/stalls', [AuthController::class, 'storeStall'])->name('stalls.store');
    Route::put('/stalls/{stall}', [AuthController::class, 'updateStall'])->name('stalls.update');
    Route::get('/rentals', [AuthController::class, 'rentals'])->name('rentals');
    Route::post('/rentals', [AuthController::class, 'storeRental'])->name('rentals.store');
    Route::put('/rentals/{rental}', [AuthController::class, 'updateRental'])->name('rentals.update');
    Route::post('/vendors', [AuthController::class, 'storeVendor'])->name('vendors.store');
    Route::put('/vendors/{vendor}', [AuthController::class, 'updateVendor'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [AuthController::class, 'destroyVendor'])->name('vendors.destroy');
    Route::post('/vendors/{vendor}/payments', [AuthController::class, 'storeVendorPaymentForAdmin'])->name('vendors.payments.store');
    Route::patch('/vendors/{vendor}/payments/{payment}/paid', [AuthController::class, 'markVendorPaymentAsPaid'])->name('vendors.payments.paid');
    Route::get('/payments', [AuthController::class, 'payments'])->name('payments');
    Route::get('/payments/create', [AuthController::class, 'createPayment'])->name('payments.create');
    Route::post('/payments', [AuthController::class, 'storePayment'])->name('payments.store');
    Route::get('/due-dates', [AuthController::class, 'dueDates'])->name('due-dates');
    Route::get('/reports', [AuthController::class, 'reports'])->name('reports');
});

Route::middleware(['auth', 'role:vendor'])->group(function () {
    Route::get('/vendor-dashboard', [AuthController::class, 'vendorDashboard'])->name('vendor.dashboard');
    Route::get('/my-stall', [AuthController::class, 'vendorStall'])->name('vendor.stall');
    Route::get('/vendor-payments', [AuthController::class, 'vendorPayments'])->name('vendor.payments');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
