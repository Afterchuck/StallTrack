<?php

namespace App\Http\Controllers;

use App\Models\VendorSupportRequest;
use App\Notifications\VendorUpdateNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSupportRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate([
            'status' => ['nullable', 'in:Open,In Progress,Resolved'],
        ])['status'] ?? null;

        return view('admin.support.index', [
            'supportRequests' => VendorSupportRequest::with('user')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(VendorSupportRequest $supportRequest): View
    {
        return view('admin.support.show', [
            'supportRequest' => $supportRequest->load(['user', 'responder']),
        ]);
    }

    public function update(Request $request, VendorSupportRequest $supportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Open,In Progress,Resolved'],
            'admin_response' => ['nullable', 'string', 'max:5000'],
        ]);

        $statusChanged = $supportRequest->status !== $validated['status'];
        $responseChanged = $supportRequest->admin_response !== $validated['admin_response'];

        $updates = ['status' => $validated['status']];

        if ($responseChanged) {
            $updates['admin_response'] = $validated['admin_response'];
            $updates['responded_by'] = $validated['admin_response'] ? $request->user()->id : null;
            $updates['responded_at'] = $validated['admin_response'] ? now() : null;
        }

        $supportRequest->update($updates);

        if ($statusChanged || $responseChanged) {
            $message = $responseChanged && $validated['admin_response']
                ? 'Market Administration replied to your support request.'
                : "Market Administration changed your support request status to {$supportRequest->status}.";

            $supportRequest->user->notify(new VendorUpdateNotification(
                'Your support request was updated',
                $message,
            ));
        }

        return redirect()->route('admin.support.show', $supportRequest)->with('success', 'Support request updated.');
    }
}
