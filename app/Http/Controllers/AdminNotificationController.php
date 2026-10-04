<?php

namespace App\Http\Controllers;

use App\Notifications\VendorMessageNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.notifications.index', [
            'notifications' => $request->user()->notifications()
                ->where('type', VendorMessageNotification::class)
                ->latest()
                ->paginate(20),
        ]);
    }
}
