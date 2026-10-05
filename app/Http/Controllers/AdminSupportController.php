<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use App\Notifications\SupportRequestSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSupportController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.support-inbox', [
            'notifications' => $request->user()->notifications()->where('type', SupportRequestSubmitted::class)->paginate(10, ['*'], 'notifications_page'),
            'supportRequests' => SupportRequest::with('vendor')->latest()->paginate(15, ['*'], 'requests_page'),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notice = $request->user()->notifications()->where('type', SupportRequestSubmitted::class)->findOrFail($notification);
        $supportRequest = SupportRequest::find($notice->data['support_request_id']);
        $notice->markAsRead();

        return $supportRequest
            ? redirect()->route('admin.support.show', $supportRequest)
            : redirect()->route('admin.notifications')->with('success', 'This support request is no longer available.');
    }

    public function show(SupportRequest $supportRequest): View
    {
        return view('admin.support-request', ['supportRequest' => $supportRequest->load('vendor')]);
    }

    public function update(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_reply' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:Open,In progress,Resolved'],
        ]);
        $supportRequest->update($validated + ['replied_at' => now()]);

        return redirect()->route('admin.support.show', $supportRequest)->with('success', 'Reply saved. The vendor can now see it in Help & Support.');
    }
}
