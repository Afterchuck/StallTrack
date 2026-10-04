<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\VendorMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorSupportController extends Controller
{
    public function index(Request $request): View
    {
        return view('vendor.support', [
            'supportRequests' => $request->user()->supportRequests()->latest()->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:Account,Stall,Payments,Rentals and contracts,Other'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $supportRequest = $request->user()->supportRequests()->create($validated);

        User::where('role', 'admin')->each(function (User $admin) use ($supportRequest): void {
            $admin->notify(new VendorMessageNotification($supportRequest));
        });

        return redirect()->route('vendor.support.index')->with('success', 'Your support request was sent to Market Administration.');
    }
}
