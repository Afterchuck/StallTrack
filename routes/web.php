<?php

use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\RentalBillingController;
use App\Http\Controllers\VendorNotificationController;
use App\Http\Controllers\VendorSupportController;
use App\Http\Middleware\EnsureVendorApproved;
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
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registration')->name('register.store');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin-notifications', [AdminSupportController::class, 'index'])->name('admin.notifications');
    Route::post('/admin-notifications/{notification}/read', [AdminSupportController::class, 'read'])->name('admin.notifications.read');
    Route::get('/support-requests/{supportRequest}', [AdminSupportController::class, 'show'])->name('admin.support.show');
    Route::patch('/support-requests/{supportRequest}', [AdminSupportController::class, 'update'])->name('admin.support.update');
    Route::get('/announcements', [AuthController::class, 'announcements'])->name('announcements');
    Route::post('/announcements', [AuthController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AuthController::class, 'editAnnouncement'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AuthController::class, 'updateAnnouncement'])->name('announcements.update');
    Route::patch('/announcements/{announcement}/unpublish', [AuthController::class, 'unpublishAnnouncement'])->name('announcements.unpublish');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/vendors', [AuthController::class, 'vendors'])->name('vendors.index');
    Route::get('/vendors/create', [AuthController::class, 'createVendor'])->name('vendors.create');
    Route::get('/vendors/{vendor}', [AuthController::class, 'showVendor'])->name('vendors.show');
    Route::get('/vendors/{vendor}/edit', [AuthController::class, 'editVendor'])->name('vendors.edit');
    Route::patch('/vendors/{vendor}/approval', [AuthController::class, 'updateVendorApproval'])->name('vendors.approval.update');
    Route::get('/stalls', [AuthController::class, 'stalls'])->name('stalls');
    Route::post('/stalls', [AuthController::class, 'storeStall'])->name('stalls.store');
    Route::put('/stalls/{stall}', [AuthController::class, 'updateStall'])->name('stalls.update');
    Route::delete('/stalls/{stall}', [AuthController::class, 'destroyStall'])->name('stalls.destroy');
    Route::get('/rentals', [AuthController::class, 'rentals'])->name('rentals');
    Route::post('/rentals', [AuthController::class, 'storeRental'])->name('rentals.store');
    Route::put('/rentals/{rental}', [AuthController::class, 'updateRental'])->name('rentals.update');
    Route::delete('/rentals/{rental}', [AuthController::class, 'destroyRental'])->name('rentals.destroy');
    Route::post('/vendors', [AuthController::class, 'storeVendor'])->name('vendors.store');
    Route::put('/vendors/{vendor}', [AuthController::class, 'updateVendor'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [AuthController::class, 'destroyVendor'])->name('vendors.destroy');
    Route::post('/vendors/{vendor}/payments', [AuthController::class, 'storeVendorPaymentForAdmin'])->name('vendors.payments.store');
    Route::patch('/vendors/{vendor}/payments/{payment}/paid', [AuthController::class, 'markVendorPaymentAsPaid'])->name('vendors.payments.paid');
    Route::get('/payments', [BillingController::class, 'index'])->name('payments');
    Route::get('/rental-billing', [RentalBillingController::class, 'index'])->name('rental-billing');
    Route::get('/rental-billing/{rental}/preview', [RentalBillingController::class, 'preview'])->name('rental-billing.preview');
    Route::post('/rental-billing/{rental}/send', [RentalBillingController::class, 'send'])->name('rental-billing.send');
    Route::post('/bills/{bill}/notify', [RentalBillingController::class, 'notify'])->name('bills.notify');
    Route::get('/payments/create', [BillingController::class, 'index'])->name('payments.create');
    Route::post('/bills', [BillingController::class, 'store'])->name('bills.store');
    Route::get('/bills/{bill}', [BillingController::class, 'show'])->name('bills.show');
    Route::post('/bills/{bill}/payments', [BillingController::class, 'record'])->name('bills.payments.store');
    Route::post('/bills/{bill}/receipts', [BillingController::class, 'allocate'])->name('bills.receipts.allocate');
    Route::patch('/bills/{bill}/payments/{payment}/reverse', [BillingController::class, 'reverse'])->name('bills.payments.reverse');
    Route::post('/payments', [AuthController::class, 'storePayment'])->name('payments.store');
    Route::get('/due-dates', [AuthController::class, 'dueDates'])->name('due-dates');
    Route::get('/reports', [AuthController::class, 'reports'])->name('reports');
});

Route::middleware(['auth', 'role:vendor', EnsureVendorApproved::class])->group(function () {
    Route::get('/vendor-dashboard', [AuthController::class, 'vendorDashboard'])->name('vendor.dashboard');
    Route::get('/my-stall', [AuthController::class, 'vendorStall'])->name('vendor.stall');
    Route::get('/vendor-payments', [AuthController::class, 'vendorPayments'])->name('vendor.payments');
    Route::get('/vendor-support', [VendorSupportController::class, 'index'])->name('vendor.support');
    Route::post('/vendor-support', [VendorSupportController::class, 'store'])->name('vendor.support.store');
    Route::get('/vendor-notifications', [VendorNotificationController::class, 'index'])->name('vendor.notifications');
    Route::post('/vendor-notifications/{notification}/read', [VendorNotificationController::class, 'read'])->name('vendor.notifications.read');
    Route::get('/vendor-bills/{bill}', [VendorNotificationController::class, 'showBill'])->name('vendor.bills.show');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
