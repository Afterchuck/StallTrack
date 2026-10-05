<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class VendorNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = Vendor::forUser($request->user());

        return view('vendor.notifications', [
            'notifications' => $vendor
                ? $vendor->notifications()->paginate(15)
                : new LengthAwarePaginator([], 0, 15),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $vendor = Vendor::forUser($request->user());
        abort_unless($vendor, 404);
        $notice = $vendor->notifications()->findOrFail($notification);
        $bill = $vendor->bills()->findOrFail($notice->data['bill_id']);
        $notice->markAsRead();

        return redirect()->route('vendor.bills.show', $bill);
    }

    public function showBill(Request $request, int $bill): View
    {
        $vendor = Vendor::forUser($request->user());
        abort_unless($vendor, 404);

        return view('vendor.bill', [
            'bill' => $vendor->bills()->with('payments')->findOrFail($bill),
        ]);
    }
}
