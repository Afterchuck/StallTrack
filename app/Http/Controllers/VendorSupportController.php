<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\SupportRequestSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VendorSupportController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = Vendor::forUser($request->user());

        return view('vendor.support', [
            'supportRequests' => $vendor
                ? $vendor->supportRequests()->latest()->paginate(10)
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = Vendor::forUser($request->user());
        abort_unless($vendor, 404);

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($vendor, $validated): void {
            $supportRequest = $vendor->supportRequests()->create($validated);
            $supportRequest->setRelation('vendor', $vendor);

            foreach (User::where('role', 'admin')->lazyById() as $admin) {
                $admin->notify(new SupportRequestSubmitted($supportRequest));
            }
        });

        return redirect()->route('vendor.support')->with('success', 'Your support request has been sent to Market Administration.');
    }
}
