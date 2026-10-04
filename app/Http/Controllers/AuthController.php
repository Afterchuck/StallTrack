<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\Stall;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\VendorUpdateNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Those credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($request->user()->role === 'vendor'
            ? route('vendor.dashboard')
            : route('dashboard'));
    }

    public function showRegistration(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'digits:10'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'mobile_number.digits' => 'Enter the 10 digits after +63.',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'],
            'mobile_number' => '+63'.$validated['mobile_number'],
            'password' => Hash::make($validated['password']),
            'role' => 'vendor',
        ]);

        Vendor::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'contact_number' => $user->mobile_number,
            'status' => 'Pending',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->role === 'vendor' ? 'vendor.dashboard' : 'dashboard');
    }

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'vendors' => Vendor::latest()->get(),
            'payments' => Payment::latest('paid_at')->take(4)->get(),
        ]);
    }

    public function vendorDashboard(): View
    {
        return view('vendor.dashboard', [
            'vendor' => $this->currentVendor(),
            'payments' => Payment::where('vendor_id', $this->currentVendor()?->id)
                ->orWhere(fn ($query) => $query->whereNull('vendor_id')->where('vendor_name', auth()->user()->name))
                ->latest('paid_at')->get(),
        ]);
    }

    public function vendorStall(): View
    {
        return view('vendor.stall', ['vendor' => $this->currentVendor()]);
    }

    public function vendorPayments(): View
    {
        return view('vendor.payments', [
            'vendor' => $this->currentVendor(),
            'payments' => Payment::where('vendor_id', $this->currentVendor()?->id)
                ->orWhere(fn ($query) => $query->whereNull('vendor_id')->where('vendor_name', auth()->user()->name))
                ->latest('paid_at')->paginate(10),
        ]);
    }

    public function vendorPaymentReceipt(Request $request, Payment $payment): View
    {
        $vendor = $this->currentVendor();

        abort_unless($vendor && (
            $payment->vendor_id === $vendor->id
            || ($payment->vendor_id === null && $payment->vendor_name === $request->user()->name)
        ), 404);
        abort_unless($payment->status === 'Paid', 404);

        return view('vendor.receipt', [
            'vendor' => $vendor,
            'payment' => $payment,
        ]);
    }

    public function vendorNotifications(Request $request): View
    {
        return view('vendor.notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(15),
        ]);
    }

    public function markVendorNotificationAsRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllVendorNotificationsAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function storeVendorPayment(Request $request): RedirectResponse
    {
        $request->merge(['vendor_name' => $request->user()->name]);

        $validated = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
        ]);

        Payment::create([...$validated, 'vendor_id' => $this->currentVendor()?->id]);

        return redirect()->route('vendor.dashboard')->with('success', 'Payment submitted successfully.');
    }

    public function createVendor(): View
    {
        $stalls = Stall::where('status', 'Available')
            ->orderBy('stall_number')
            ->get();

        return view('admin.vendors.create', [
            'stalls' => $stalls,
        ]);
    }

    public function showVendor(Vendor $vendor): View
    {
        return view('admin.vendors.show', [
            'vendor' => $vendor,
            'payments' => $vendor->payments()->latest('paid_at')->get(),
        ]);
    }

    public function editVendor(Vendor $vendor): View
    {
        $stalls = Stall::where('status', 'Available')
            ->when($vendor->stall_number, function ($query) use ($vendor): void {
                $query->orWhere('stall_number', $vendor->stall_number);
            })
            ->orderBy('stall_number')
            ->get();

        return view('admin.vendors.edit', [
            'vendor' => $vendor,
            'stalls' => $stalls,
        ]);
    }

    public function updateVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stall_number' => ['required', 'string', 'max:20'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:vendors,email,'.$vendor->id],
            'residential_address' => ['nullable', 'string', 'max:1000'],
            'market_section' => ['required', 'string', 'max:100'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['required', 'date', 'after_or_equal:contract_start_date'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        DB::transaction(function () use ($validated, $vendor): void {
            $originalName = $vendor->name;
            $originalEmail = $vendor->email;
            $oldStallNumber = $vendor->stall_number;

            $vendor->update([...$validated, 'contract_until' => $validated['contract_end_date']]);
            Payment::where('vendor_name', $originalName)->update(['vendor_name' => $vendor->name, 'vendor_id' => $vendor->id]);

            User::where('email', $originalEmail)
                ->where('role', 'vendor')
                ->update(['name' => $vendor->name, 'email' => $vendor->email]);

            if ($oldStallNumber && $oldStallNumber !== $vendor->stall_number) {
                if (! Vendor::where('stall_number', $oldStallNumber)->where('id', '!=', $vendor->id)->where('status', 'Active')->exists()) {
                    Stall::where('stall_number', $oldStallNumber)->update(['status' => 'Available']);
                }
                Rental::where('vendor_id', $vendor->id)
                    ->whereHas('stall', fn ($q) => $q->where('stall_number', $oldStallNumber))
                    ->update(['status' => 'Terminated']);
            }

            if ($stall = Stall::where('stall_number', $vendor->stall_number)->first()) {
                $stall->update([
                    'status' => $vendor->status === 'Active' ? 'Occupied' : 'Inactive',
                ]);

                if ($vendor->status === 'Active') {
                    Rental::updateOrCreate(
                        [
                            'vendor_id' => $vendor->id,
                            'stall_id' => $stall->id,
                        ],
                        [
                            'contract_number' => 'RNT-'.str_replace('-', '', (string) $stall->stall_number),
                            'start_date' => $vendor->contract_start_date,
                            'end_date' => $vendor->contract_end_date,
                            'rent_amount' => $vendor->monthly_rent,
                            'billing_cycle' => $vendor->billing_cycle,
                            'status' => 'Active',
                        ]
                    );
                }
            }
        });

        if ($vendor->wasChanged()) {
            $this->notifyVendor($vendor, 'Your account was updated', 'Market Administration updated your vendor, stall, or contract details.');
        }

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor details and linked payment records updated.');
    }

    public function destroyVendor(Vendor $vendor): RedirectResponse
    {
        DB::transaction(function () use ($vendor): void {
            if ($vendor->stall_number) {
                Stall::where('stall_number', $vendor->stall_number)->update(['status' => 'Available']);
                Rental::where('vendor_id', $vendor->id)->update(['status' => 'Terminated']);
            }
            User::where('email', $vendor->email)->where('role', 'vendor')->delete();
            $vendor->delete();
        });

        return redirect()->route('vendors.index')->with('success', 'Vendor account deleted. Payment records were retained for audit history.');
    }

    public function storeVendorPaymentForAdmin(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
        ]);

        Payment::create([...$validated, 'vendor_id' => $vendor->id, 'vendor_name' => $vendor->name]);
        $this->notifyVendor($vendor, 'A payment was recorded', 'Market Administration recorded a payment for your account.');

        return redirect()->route('vendors.show', $vendor)->with('success', 'Payment recorded and reflected in the vendor portal.');
    }

    public function markVendorPaymentAsPaid(Request $request, Vendor $vendor, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless($payment->vendor_id === $vendor->id || $payment->vendor_name === $vendor->name, 404);

        $payment->update(['status' => 'Paid']);

        if ($payment->wasChanged('status')) {
            $this->notifyVendor($vendor, 'Payment marked as paid', "Receipt {$payment->receipt_number} was marked as paid by Market Administration.");
        }

        return redirect()->route('vendors.show', $vendor)->with('success', 'Payment marked as paid.');
    }

    public function vendors(Request $request): View|JsonResponse
    {
        $query = Vendor::latest();
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($vendorQuery) use ($search): void {
                $vendorQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('market_section', 'like', "%{$search}%")
                    ->orWhere('stall_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('stall_type')) {
            $query->where('market_section', $request->input('stall_type'));
        }

        if ($request->filled('contract_status')) {
            $query->where('status', $request->input('contract_status'));
        }

        $vendors = $query->paginate(4)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $vendors->getCollection()->map(fn (Vendor $vendor): array => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'market_section' => $vendor->market_section ?: 'Market Vendor',
                    'contact_number' => $vendor->contact_number ?: '+63 917 555 0192',
                    'stall_number' => $vendor->stall_number,
                    'monthly_rent' => number_format((float) ($vendor->monthly_rent ?: 3500), 0),
                    'status' => $vendor->status === 'Active' ? 'Active' : 'Pending Review',
                ])->values(),
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'total' => $vendors->total(),
                'from' => $vendors->firstItem(),
                'to' => $vendors->lastItem(),
            ]);
        }

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'availableStalls' => Stall::where('status', 'Available')->orderBy('stall_number')->get(),
        ]);
    }

    public function stalls(): View
    {
        return view('admin.stalls.index', [
            'stalls' => Stall::with('rentals.vendor')->orderBy('stall_number')->get(),
            'availableStalls' => Stall::where('status', 'Available')->orderBy('stall_number')->get(),
        ]);
    }

    public function storeStall(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stall_number' => ['required', 'string', 'max:20', 'unique:stalls,stall_number'],
            'market_section' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'stall_type' => ['nullable', 'string', 'max:50'],
            'dimensions' => ['nullable', 'string', 'max:50'],
            'monthly_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Available,Occupied,Inactive'],
        ]);

        Stall::create($validated);

        return redirect()->route('stalls')->with('success', "Stall {$validated['stall_number']} registered successfully.");
    }

    public function updateStall(Request $request, Stall $stall): RedirectResponse
    {
        $validated = $request->validate([
            'stall_number' => ['required', 'string', 'max:20', 'unique:stalls,stall_number,'.$stall->id],
            'market_section' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'stall_type' => ['nullable', 'string', 'max:50'],
            'dimensions' => ['nullable', 'string', 'max:50'],
            'monthly_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Available,Occupied,Inactive'],
        ]);

        $stall->update($validated);

        if ($stall->wasChanged()) {
            $vendors = Vendor::whereIn('id', Rental::where('stall_id', $stall->id)
                ->where('status', 'Active')
                ->pluck('vendor_id')
                ->unique())
                ->get();

            foreach ($vendors as $vendor) {
                $this->notifyVendor($vendor, 'Your stall was updated', "Market Administration updated the details for stall {$stall->stall_number}.");
            }
        }

        return redirect()->route('stalls')->with('success', "Stall {$stall->stall_number} updated successfully.");
    }

    public function rentals(): View
    {
        return view('admin.rentals.index', [
            'rentals' => Rental::with(['vendor', 'stall'])->latest('start_date')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'stalls' => Stall::orderBy('stall_number')->get(),
            'availableStalls' => Stall::where('status', 'Available')->orderBy('stall_number')->get(),
        ]);
    }

    public function storeRental(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'stall_id' => ['required', 'exists:stalls,id'],
            'contract_number' => ['required', 'string', 'max:50', 'unique:rentals,contract_number'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly,Bi-weekly,Weekly'],
            'status' => ['required', 'in:Active,Inactive,Pending'],
        ]);

        DB::transaction(function () use ($validated): void {
            Rental::create($validated);
            $stall = Stall::find($validated['stall_id']);
            $vendor = Vendor::find($validated['vendor_id']);

            if ($stall) {
                $stall->update([
                    'status' => $validated['status'] === 'Active' ? 'Occupied' : 'Available',
                ]);
            }

            if ($vendor && $validated['status'] === 'Active') {
                $vendor->update([
                    'stall_number' => $stall?->stall_number,
                    'market_section' => $stall?->market_section ?? $vendor->market_section,
                    'monthly_rent' => $validated['rent_amount'],
                    'billing_cycle' => $validated['billing_cycle'],
                    'contract_start_date' => $validated['start_date'],
                    'contract_end_date' => $validated['end_date'],
                    'contract_until' => $validated['end_date'],
                    'status' => 'Active',
                ]);
            }
        });

        $vendor = Vendor::find($validated['vendor_id']);
        if ($vendor) {
            $this->notifyVendor($vendor, 'A rental contract was added', "Market Administration added rental contract {$validated['contract_number']} to your account.");
        }

        return redirect()->route('rentals')->with('success', "Rental contract {$validated['contract_number']} executed successfully.");
    }

    public function updateRental(Request $request, Rental $rental): RedirectResponse
    {
        $previousVendorId = $rental->vendor_id;

        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'stall_id' => ['required', 'exists:stalls,id'],
            'contract_number' => ['required', 'string', 'max:50', 'unique:rentals,contract_number,'.$rental->id],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly,Bi-weekly,Weekly'],
            'status' => ['required', 'in:Active,Expired,Terminated,Pending'],
        ]);

        DB::transaction(function () use ($validated, $rental): void {
            $oldStallId = $rental->stall_id;
            $rental->update($validated);

            $newStall = Stall::find($validated['stall_id']);
            $vendor = Vendor::find($validated['vendor_id']);

            if ($oldStallId != $validated['stall_id']) {
                $hasOtherActive = Rental::where('stall_id', $oldStallId)
                    ->where('id', '!=', $rental->id)
                    ->where('status', 'Active')
                    ->exists();
                if (! $hasOtherActive) {
                    Stall::where('id', $oldStallId)->update(['status' => 'Available']);
                }
            }

            if ($newStall) {
                if ($validated['status'] === 'Active') {
                    $newStall->update(['status' => 'Occupied']);
                } elseif (in_array($validated['status'], ['Expired', 'Terminated'], true)) {
                    $hasOtherActive = Rental::where('stall_id', $newStall->id)
                        ->where('id', '!=', $rental->id)
                        ->where('status', 'Active')
                        ->exists();
                    if (! $hasOtherActive) {
                        $newStall->update(['status' => 'Available']);
                    }
                }
            }

            if ($vendor && $validated['status'] === 'Active') {
                $vendor->update([
                    'stall_number' => $newStall?->stall_number ?? $vendor->stall_number,
                    'market_section' => $newStall?->market_section ?? $vendor->market_section,
                    'monthly_rent' => $validated['rent_amount'],
                    'billing_cycle' => $validated['billing_cycle'],
                    'contract_start_date' => $validated['start_date'],
                    'contract_end_date' => $validated['end_date'],
                    'contract_until' => $validated['end_date'],
                    'status' => 'Active',
                ]);
            }
        });

        if ($rental->wasChanged()) {
            $affectedVendors = Vendor::whereIn('id', array_unique([$previousVendorId, $rental->vendor_id]))->get();

            foreach ($affectedVendors as $vendor) {
                $this->notifyVendor($vendor, 'A rental contract was updated', "Market Administration updated rental contract {$rental->contract_number}.");
            }
        }

        return redirect()->route('rentals')->with('success', "Rental contract {$rental->contract_number} updated successfully.");
    }

    public function storeVendor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stall_number' => ['required', 'string', 'max:20'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'residential_address' => ['nullable', 'string', 'max:1000'],
            'market_section' => ['required', 'string', 'max:100'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly,Bi-weekly,Weekly'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['required', 'date', 'after_or_equal:contract_start_date'],
            'status' => ['required', 'in:Active,Inactive'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('vendor-photos', 'public');
        }

        $validated['contract_until'] = $validated['contract_end_date'];
        unset($validated['photo']);
        $vendor = Vendor::create($validated);

        if ($stall = Stall::where('stall_number', $vendor->stall_number)->first()) {
            $stall->update([
                'status' => $vendor->status === 'Active' ? 'Occupied' : 'Inactive',
            ]);

            if ($vendor->status === 'Active') {
                Rental::updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'stall_id' => $stall->id,
                    ],
                    [
                        'contract_number' => 'RNT-'.str_replace('-', '', (string) $stall->stall_number),
                        'start_date' => $vendor->contract_start_date,
                        'end_date' => $vendor->contract_end_date,
                        'rent_amount' => $vendor->monthly_rent,
                        'billing_cycle' => $vendor->billing_cycle,
                        'status' => 'Active',
                    ]
                );
            }
        }

        return redirect()->route('vendors.index')->with('success', "Vendor {$vendor->name} added successfully.");
    }

    public function payments(): View
    {
        return view('admin.payments.index', ['payments' => Payment::latest('paid_at')->get(), 'active' => 'payments']);
    }

    public function createPayment(): View
    {
        return view('admin.payments.create', ['vendors' => Vendor::orderBy('name')->get()]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
        ]);

        $vendor = Vendor::where('name', $validated['vendor_name'])->firstOrFail();
        Payment::create([...$validated, 'vendor_id' => $vendor->id]);
        $this->notifyVendor($vendor, 'A payment was recorded', 'Market Administration recorded a payment for your account.');

        return redirect()->route('payments')->with('success', 'Payment recorded successfully.');
    }

    public function dueDates(): View
    {
        return view('admin.section', ['title' => 'Due Dates', 'description' => 'Keep contracts and payment deadlines on schedule.', 'active' => 'due-dates']);
    }

    public function reports(): View
    {
        return view('admin.section', ['title' => 'Reports', 'description' => 'Review registration, payment, and stall activity.', 'active' => 'reports']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function currentVendor(): ?Vendor
    {
        return auth()->user()->vendor
            ?? Vendor::where('email', auth()->user()->email)
                ->orWhere('name', auth()->user()->name)
                ->first();
    }

    private function notifyVendor(Vendor $vendor, string $title, string $message): void
    {
        $recipient = $vendor->user;

        if (! $recipient) {
            $recipient = User::where('role', 'vendor')
                ->where(function ($query) use ($vendor): void {
                    if ($vendor->email) {
                        $query->where('email', $vendor->email);
                    }

                    $query->orWhere('name', $vendor->name);
                })
                ->first();
        }

        $recipient?->notify(new VendorUpdateNotification($title, $message));
    }
}
