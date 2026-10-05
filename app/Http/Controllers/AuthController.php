<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Stall;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Those credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = $request->user();

        if ($user->role === 'vendor' && $user->vendor?->approval_status !== 'Approved') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your vendor account has not been approved yet.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($user->role === 'vendor'
            ? route('vendor.dashboard')
            : route('dashboard'));
    }

    public function showRegistration(): View
    {
        abort_unless(config('security.registration_enabled'), 404);

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(config('security.registration_enabled'), 404);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email', 'unique:vendors,email'],
            'mobile_number' => ['required', 'string', 'digits:10'],
            'password' => ['required', 'string', 'confirmed', Password::min(12), function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('The password must be no more than 72 bytes.');
                }
            }],
            'terms' => ['accepted'],
        ], [
            'mobile_number.digits' => 'Enter the 10 digits after +63.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
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
                'approval_status' => 'Pending',
            ]);

            return $user;
        });

        return redirect()->route('login')->with('status', 'Your account has been created and is awaiting admin approval.');
    }

    public function dashboard(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        return view('admin.dashboard', [
            'vendors' => Vendor::latest()->get(),
            'totalStalls' => Stall::count(),
            'occupiedStalls' => Stall::where('status', 'Occupied')->count(),
            'availableStalls' => Stall::where('status', 'Available')->count(),
            'collectedMonthly' => Payment::where('status', 'Paid')->whereBetween('paid_at', [today()->startOfMonth(), today()->endOfMonth()])->sum('amount'),
            'outstandingTotal' => Bill::sum('amount') - Bill::sum('paid_amount'),
            'dueToday' => Bill::outstanding()->whereDate('due_date', today())->count(),
            'overdueCount' => Bill::outstanding()->whereDate('due_date', '<', today())->count(),
            'outstandingBills' => Bill::with('vendor')->search($request->input('search'))->outstanding()->orderBy('due_date')->orderBy('id')->limit(8)->get(),
            'overdueBills' => Bill::with('vendor')->search($request->input('search'))->outstanding()->whereDate('due_date', '<', today())->orderBy('due_date')->limit(8)->get(),
        ]);
    }

    public function vendorDashboard(): View
    {
        $vendor = $this->currentVendor();

        return view('vendor.dashboard', [
            'announcements' => Announcement::published()->orderByDesc('is_pinned')
                ->orderByDesc('published_at')->orderByDesc('id')->paginate(5, ['*'], 'announcements_page'),
            'vendor' => $vendor,
            'bills' => Bill::where('vendor_id', $vendor?->id ?? 0)->outstanding()->orderBy('due_date')->get(),
            'payments' => Payment::where('vendor_id', $vendor?->id ?? 0)
                ->latest('paid_at')->get(),
        ]);
    }

    public function announcements(Request $request): View
    {
        return view('admin.announcements', [
            'announcements' => $this->announcementListing($request),
            'announcement' => new Announcement,
        ]);
    }

    public function editAnnouncement(Request $request, Announcement $announcement): View
    {
        return view('admin.announcements', [
            'announcements' => $this->announcementListing($request),
            'announcement' => $announcement,
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        Announcement::create([...$this->announcementData($request), 'user_id' => $request->user()->id]);

        return redirect()->route('announcements')->with('success', 'Announcement saved.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->announcementData($request, $announcement));

        return redirect()->route('announcements')->with('success', 'Announcement updated.');
    }

    public function unpublishAnnouncement(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['published_at' => null]);

        return redirect()->route('announcements')->with('success', 'Announcement unpublished. It is now a draft.');
    }

    /**
     * @return array{title: string, message: string, is_pinned: bool, expires_at: ?Carbon, published_at: ?Carbon}
     */
    private function announcementData(Request $request, ?Announcement $announcement = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'in:Draft,Published'],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);

        return [
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_pinned' => $request->boolean('is_pinned'),
            'expires_at' => empty($validated['expires_at']) ? null : Carbon::parse($validated['expires_at'])->endOfDay(),
            'published_at' => $validated['status'] === 'Published' ? ($announcement?->published_at ?? now()) : null,
        ];
    }

    public function vendorStall(): View
    {
        return view('vendor.stall', ['vendor' => $this->currentVendor()]);
    }

    public function vendorPayments(): View
    {
        $vendor = $this->currentVendor();

        return view('vendor.payments', [
            'vendor' => $vendor,
            'totalPaid' => Payment::where('vendor_id', $vendor?->id ?? 0)->where('status', 'Paid')->sum('amount'),
            'bills' => Bill::where('vendor_id', $vendor?->id ?? 0)->orderByDesc('period_start')->paginate(10, ['*'], 'bills_page'),
            'payments' => Payment::with('bill')->where('vendor_id', $vendor?->id ?? 0)
                ->latest('paid_at')->latest('id')->paginate(10),
        ]);
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

    public function showStall(Stall $stall): View
    {
        $stall->load(['rentals' => fn ($query) => $query->active()->with('vendor')]);

        return view('admin.stalls.show', [
            'stall' => $stall,
            'rental' => $stall->rentals->first(),
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
            'stall_number' => ['required', 'string', 'max:20', 'exists:stalls,stall_number'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:vendors,email,'.$vendor->id],
            'residential_address' => ['nullable', 'string', 'max:1000'],
            'market_section' => ['required', 'string', 'max:100'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly,Bi-weekly,Weekly'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['required', 'date', 'after_or_equal:contract_start_date'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        DB::transaction(function () use ($validated, $vendor): void {
            $oldStallNumber = $vendor->stall_number;
            $oldStall = $oldStallNumber
                ? Stall::query()->lockForUpdate()->where('stall_number', $oldStallNumber)->first()
                : null;
            $newStall = Stall::query()->lockForUpdate()->where('stall_number', $validated['stall_number'])->firstOrFail();
            $existingRentalId = Rental::query()
                ->where('vendor_id', $vendor->id)
                ->where('stall_id', $newStall->id)
                ->where('status', 'Active')
                ->value('id');

            if ($validated['status'] === 'Active') {
                $this->ensureStallAvailable($newStall, $existingRentalId);
            }

            $vendor->update([...$validated, 'contract_until' => $validated['contract_end_date']]);
            Payment::where('vendor_id', $vendor->id)->update(['vendor_name' => $vendor->name]);

            $vendor->user()->where('role', 'vendor')->update(['name' => $vendor->name]);

            if ($oldStallNumber && $oldStallNumber !== $vendor->stall_number) {
                if (! Vendor::where('stall_number', $oldStallNumber)->where('id', '!=', $vendor->id)->where('status', 'Active')->exists()) {
                    $oldStall?->update(['status' => 'Available']);
                }
                Rental::where('vendor_id', $vendor->id)
                    ->whereHas('stall', fn ($q) => $q->where('stall_number', $oldStallNumber))
                    ->update(['status' => 'Terminated']);
            }

            $newStall->update(['status' => $vendor->status === 'Active' ? 'Occupied' : 'Available']);

            if ($vendor->status === 'Active') {
                Rental::updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'stall_id' => $newStall->id,
                    ],
                    [
                        'contract_number' => 'RNT-'.str_replace('-', '', (string) $newStall->stall_number).'-'.$vendor->id,
                        'start_date' => $vendor->contract_start_date,
                        'end_date' => $vendor->contract_end_date,
                        'rent_amount' => $vendor->monthly_rent,
                        'billing_cycle' => $vendor->billing_cycle,
                        'status' => 'Active',
                    ]
                );
            } else {
                Rental::where('vendor_id', $vendor->id)->where('status', 'Active')->update(['status' => 'Terminated']);
            }
        });

        $this->recordActivity('updated', $vendor, "Updated vendor {$vendor->name}.");

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor details and linked payment records updated.');
    }

    public function updateVendorApproval(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'approval_status' => ['required', 'in:Pending,Approved,Not approved'],
        ]);

        if ($vendor->user_id === null || $vendor->user?->role !== 'vendor') {
            throw ValidationException::withMessages([
                'approval_status' => 'This vendor does not have a registered vendor account.',
            ]);
        }

        $vendor->update(['approval_status' => $validated['approval_status']]);
        $this->recordActivity('updated', $vendor, "Changed {$vendor->name}'s account approval to {$validated['approval_status']}.");

        return redirect()->route('vendors.index')
            ->with('success', "{$vendor->name}'s account status is now {$validated['approval_status']}.");
    }

    public function destroyVendor(Vendor $vendor): RedirectResponse
    {
        $vendorName = $vendor->name;
        $vendorId = $vendor->id;
        DB::transaction(function () use ($vendor): void {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($vendor->bills()->exists() || $vendor->payments()->exists() || $vendor->rentals()->exists()) {
                throw ValidationException::withMessages([
                    'vendor' => 'This vendor has rental or payment history and cannot be deleted. Set the vendor to inactive instead.',
                ]);
            }

            if ($vendor->stall_number !== null) {
                $stall = Stall::query()->where('stall_number', $vendor->stall_number)->lockForUpdate()->first();
                if ($stall !== null && ! Rental::query()->active()->where('stall_id', $stall->id)->exists()) {
                    $stall->update(['status' => 'Available']);
                }
            }

            $vendor->user()->where('role', 'vendor')->delete();
            $vendor->delete();
        });

        $this->recordActivity('deleted', null, "Deleted vendor {$vendorName}.", ['vendor_id' => $vendorId]);

        return redirect()->route('vendors.index')->with('success', 'Vendor account deleted.');
    }

    public function storeVendorPaymentForAdmin(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:99999999.99'],
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
        ]);

        Payment::create([...$validated, 'vendor_id' => $vendor->id, 'vendor_name' => $vendor->name]);
        $this->recordActivity('created', $vendor, "Recorded payment for {$vendor->name}.", ['receipt_number' => $validated['receipt_number']]);

        return redirect()->route('vendors.show', $vendor)->with('success', 'Payment recorded and reflected in the vendor portal.');
    }

    public function markVendorPaymentAsPaid(Request $request, Vendor $vendor, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless($payment->vendor_id === $vendor->id, 404);

        DB::transaction(function () use ($payment, $vendor): void {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->bill_id || $payment->status === 'Reversed') {
                throw ValidationException::withMessages(['payment' => 'Use the bill payment history to manage this receipt. Reversed payments cannot be marked paid again.']);
            }
            $payment->update(['status' => 'Paid', 'recorded_by' => auth()->id()]);
            $this->recordActivity('updated', $vendor, "Marked payment {$payment->receipt_number} as paid.");
        });

        return redirect()->route('vendors.show', $vendor)->with('success', 'Payment marked as paid.');
    }

    public function vendors(Request $request): View|JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'stall_type' => ['nullable', 'string', 'max:100'],
            'contract_status' => ['nullable', 'in:Active,Pending,Inactive'],
        ]);
        $query = Vendor::with('user')->latest()->latest('id');
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
                    'approval_status' => $vendor->approval_status,
                ])->values(),
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'total' => $vendors->total(),
                'from' => $vendors->firstItem(),
                'to' => $vendors->lastItem(),
            ]);
        }

        return view('admin.vendors.index', [
            'vendors' => $vendors->loadCount(['bills', 'payments', 'rentals']),
            'pendingApplications' => Vendor::where('approval_status', 'Pending')->count(),
            'sections' => Vendor::whereNotNull('market_section')->distinct()->orderBy('market_section')->pluck('market_section'),
            'availableStalls' => Stall::where('status', 'Available')->orderBy('stall_number')->get(),
        ]);
    }

    public function stalls(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Available,Occupied,Inactive'],
            'section' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Stall::with(['rentals' => fn ($rentalQuery) => $rentalQuery->active()->with('vendor')])
            ->withCount('rentals')
            ->orderBy('stall_number');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($stallQuery) use ($search): void {
                $stallQuery->where('stall_number', 'like', "%{$search}%")
                    ->orWhere('market_section', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $stallCounts = Stall::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(status = 'Occupied') as occupied")
            ->selectRaw("sum(status = 'Available') as available")
            ->selectRaw("sum(status = 'Inactive') as inactive")
            ->first();

        return view('admin.stalls.index', [
            'stalls' => $query->when($request->filled('section'), fn ($stalls) => $stalls->where('market_section', $request->input('section')))->paginate(10)->withQueryString(),
            'sections' => Stall::distinct()->orderBy('market_section')->pluck('market_section'),
            'stallCounts' => $stallCounts,
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
            'length_m' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'width_m' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'rate_per_sqm' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', 'in:Available,Occupied,Inactive'],
        ]);

        $validated = $this->calculateStallRate($validated);
        Stall::create($validated);
        $stall = Stall::where('stall_number', $validated['stall_number'])->firstOrFail();
        $this->recordActivity('created', $stall, "Created stall {$stall->stall_number}.");

        return redirect()->route('stalls')->with('success', "Stall {$validated['stall_number']} registered successfully.");
    }

    public function updateStall(Request $request, Stall $stall): RedirectResponse
    {
        $validated = $request->validate([
            'stall_number' => ['required', 'string', 'max:20', 'unique:stalls,stall_number,'.$stall->id],
            'market_section' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'stall_type' => ['nullable', 'string', 'max:50'],
            'length_m' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'width_m' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'rate_per_sqm' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', 'in:Available,Occupied,Inactive'],
        ]);

        $validated = $this->calculateStallRate($validated);
        $stall->update($validated);
        $this->recordActivity('updated', $stall, "Updated stall {$stall->stall_number}.");

        return redirect()->route('stalls')->with('success', "Stall {$stall->stall_number} updated successfully.");
    }

    public function destroyStall(Stall $stall): RedirectResponse
    {
        $stallNumber = $stall->stall_number;
        $stallId = $stall->id;
        DB::transaction(function () use ($stall): void {
            $stall = Stall::query()->lockForUpdate()->findOrFail($stall->id);
            if ($stall->rentals()->exists()) {
                throw ValidationException::withMessages([
                    'stall' => 'This stall has rental history and cannot be deleted. Set it to inactive instead.',
                ]);
            }

            $stall->delete();
        });
        $this->recordActivity('deleted', null, "Deleted stall {$stallNumber}.", ['stall_id' => $stallId]);

        return redirect()->route('stalls')->with('success', "Stall {$stallNumber} deleted successfully.");
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function calculateStallRate(array $validated): array
    {
        $monthlyRate = round(
            (float) $validated['length_m'] * (float) $validated['width_m'] * (float) $validated['rate_per_sqm'],
            2
        );

        if ($monthlyRate > 99999999.99) {
            throw ValidationException::withMessages([
                'rate_per_sqm' => 'The calculated monthly rate cannot exceed ₱99,999,999.99.',
            ]);
        }

        $validated['dimensions'] = $validated['length_m'].'m x '.$validated['width_m'].'m';
        $validated['monthly_rate'] = $monthlyRate;

        return $validated;
    }

    public function rentals(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Active,Pending,Expired,Terminated,Inactive'],
            'cycle' => ['nullable', 'in:Monthly,Quarterly,Weekly,Bi-weekly'],
        ]);
        $query = Rental::with(['vendor', 'stall'])->withCount('bills')->latest('start_date')->latest('id')
            ->when($request->filled('cycle'), fn ($rentals) => $rentals->where('billing_cycle', $request->input('cycle')));

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($rentalQuery) use ($search): void {
                $rentalQuery->where('contract_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('stall', fn ($stallQuery) => $stallQuery->where('stall_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $rentalCounts = Rental::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(status = 'Active') as active")
            ->selectRaw("sum(status = 'Expired') as expired")
            ->first();

        return view('admin.rentals.index', [
            'rentals' => $query->paginate(10)->withQueryString(),
            'rentalCounts' => $rentalCounts,
            'expiringSoonCount' => Rental::expiringSoon()->count(),
            'vendors' => Vendor::whereDoesntHave('rentals', fn ($query) => $query->active())->orderBy('name')->get(),
            'stalls' => Stall::orderBy('stall_number')->get(),
            'availableStalls' => Stall::where('status', 'Available')
                ->whereDoesntHave('rentals', fn ($query) => $query->active())
                ->orderBy('stall_number')->get(),
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

        $vendor = null;

        DB::transaction(function () use ($validated, &$vendor): void {
            $stall = Stall::query()->lockForUpdate()->findOrFail($validated['stall_id']);
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($validated['vendor_id']);

            $this->ensureStallAvailable($stall);
            $this->ensureVendorHasNoActiveRental($vendor);

            Rental::create($validated);

            if ($validated['status'] === 'Active') {
                $stall->update([
                    'status' => 'Occupied',
                ]);

                $vendor->update([
                    'stall_number' => $stall->stall_number,
                    'market_section' => $stall->market_section,
                    'monthly_rent' => $validated['rent_amount'],
                    'billing_cycle' => $validated['billing_cycle'],
                    'contract_start_date' => $validated['start_date'],
                    'contract_end_date' => $validated['end_date'],
                    'contract_until' => $validated['end_date'],
                    'status' => 'Active',
                ]);
            }
        });

        $this->recordActivity('created', Rental::where('contract_number', $validated['contract_number'])->first(), "Created rental contract {$validated['contract_number']}.");

        return redirect()->route('rentals')->with('success', "Rental contract {$validated['contract_number']} executed successfully.");
    }

    public function updateRental(Request $request, Rental $rental): RedirectResponse
    {
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
            $oldStall = Stall::query()->lockForUpdate()->findOrFail($rental->stall_id);
            $newStall = Stall::query()->lockForUpdate()->findOrFail($validated['stall_id']);
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($validated['vendor_id']);

            if ($newStall->id !== $oldStall->id || $validated['status'] === 'Active') {
                $this->ensureStallAvailable($newStall, $rental->id);
            }

            if ($vendor->id !== $rental->vendor_id || $validated['status'] === 'Active') {
                $this->ensureVendorHasNoActiveRental($vendor, $rental->id);
            }

            $rental->update($validated);

            if ($oldStall->id !== $newStall->id && ! Rental::where('stall_id', $oldStall->id)->active()->exists()) {
                $oldStall->update(['status' => 'Available']);
            }

            if ($validated['status'] === 'Active') {
                $newStall->update(['status' => 'Occupied']);
                $this->syncVendorFromRental($vendor, $newStall, $validated);
            } elseif (! Rental::where('stall_id', $newStall->id)->active()->exists()) {
                $newStall->update(['status' => 'Available']);
                $this->clearVendorAssignmentIfUnoccupied($vendor);
            }
        });

        $this->recordActivity('updated', $rental, "Updated rental contract {$rental->contract_number}.");

        return redirect()->route('rentals')->with('success', "Rental contract {$rental->contract_number} updated successfully.");
    }

    public function destroyRental(Rental $rental): RedirectResponse
    {
        $contractNumber = $rental->contract_number;
        $rentalId = $rental->id;

        $deleted = DB::transaction(function () use ($rental): bool {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->id);
            $stall = Stall::query()->lockForUpdate()->findOrFail($rental->stall_id);
            $vendor = Vendor::query()->lockForUpdate()->findOrFail($rental->vendor_id);
            $wasActive = $rental->status === 'Active';

            if ($rental->bills()->exists()) {
                return false;
            }

            $rental->delete();

            if ($wasActive) {
                if (! Rental::query()->active()->where('stall_id', $stall->id)->exists()) {
                    $stall->update(['status' => 'Available']);
                }

                $this->clearVendorAssignmentIfUnoccupied($vendor);
            }

            return true;
        });

        if (! $deleted) {
            return back()->with('error', 'This rental has billing history and cannot be deleted. Update its status to Terminated instead.');
        }

        $this->recordActivity('deleted', null, "Deleted rental contract {$contractNumber}.", ['rental_id' => $rentalId]);

        return redirect()->route('rentals')->with('success', "Rental contract {$contractNumber} deleted successfully.");
    }

    public function storeVendor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stall_number' => ['required', 'string', 'max:20', 'exists:stalls,stall_number'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'residential_address' => ['nullable', 'string', 'max:1000'],
            'market_section' => ['required', 'string', 'max:100'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly,Bi-weekly,Weekly'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['required', 'date', 'after_or_equal:contract_start_date'],
            'status' => ['required', 'in:Active,Inactive'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('vendor-photos', 'local');
            if ($validated['photo_path'] === false) {
                throw ValidationException::withMessages(['photo' => 'The ID photo could not be stored securely. Please try again.']);
            }
        }

        $validated['contract_until'] = $validated['contract_end_date'];
        unset($validated['photo']);
        $vendor = null;

        DB::transaction(function () use ($validated, &$vendor): void {
            $stall = Stall::query()
                ->lockForUpdate()
                ->where('stall_number', $validated['stall_number'])
                ->firstOrFail();

            if ($validated['status'] === 'Active') {
                $this->ensureStallAvailable($stall);
            }

            $vendor = Vendor::create($validated);

            if ($validated['status'] === 'Active') {
                $stall->update(['status' => 'Occupied']);
                Rental::create([
                    'vendor_id' => $vendor->id,
                    'stall_id' => $stall->id,
                    'contract_number' => 'RNT-'.str_replace('-', '', (string) $stall->stall_number).'-'.$vendor->id,
                    'start_date' => $vendor->contract_start_date,
                    'end_date' => $vendor->contract_end_date,
                    'rent_amount' => $vendor->monthly_rent,
                    'billing_cycle' => $vendor->billing_cycle,
                    'status' => 'Active',
                ]);
            }
        });

        $this->recordActivity('created', $vendor, "Created vendor {$vendor->name}.");

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
            'vendor_id' => ['required', 'exists:vendors,id'],
            'amount' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:99999999.99'],
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);
        Payment::create([
            ...$validated,
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
        ]);
        $this->recordActivity('created', $vendor, "Recorded payment for {$vendor->name}.", ['receipt_number' => $validated['receipt_number']]);

        return redirect()->route('payments')->with('success', 'Payment recorded successfully.');
    }

    public function dueDates(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'days' => ['nullable', 'in:30,60,90'],
            'due_status' => ['nullable', 'in:today,overdue'],
        ]);
        $expiringRentals = Rental::with(['vendor', 'stall'])
            ->search($request->input('search'))->expiringSoon($request->integer('days') ?: 90)
            ->orderBy('end_date')
            ->orderBy('id')->paginate(10, ['*'], 'rentals_page')->withQueryString();
        $overdueBills = Bill::outstanding()->search($request->input('search'))
            ->whereDate('due_date', '<=', today())
            ->when($request->input('due_status') === 'today', fn ($bills) => $bills->whereDate('due_date', today()))
            ->when($request->input('due_status') === 'overdue', fn ($bills) => $bills->whereDate('due_date', '<', today()))
            ->orderBy('due_date')->orderBy('id')->paginate(10, ['*'], 'bills_page')->withQueryString();

        return view('admin.due-dates', [
            'expiringRentals' => $expiringRentals,
            'overdueBills' => $overdueBills,
        ]);
    }

    public function reports(Request $request): View
    {
        $request->validate([
            'section' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
        ]);
        if ($request->filled('from') && $request->filled('to') && $request->input('from') > $request->input('to')) {
            throw ValidationException::withMessages(['to' => 'The end date must be on or after the start date.']);
        }
        $stalls = Stall::query()->when($request->filled('section'), fn ($query) => $query->where('market_section', $request->input('section')))->get(['market_section', 'status']);
        $vendors = Vendor::query()->when($request->filled('section'), fn ($query) => $query->where('market_section', $request->input('section')));
        $bills = Bill::query()->when($request->filled('section'), fn ($query) => $query->whereHas('vendor', fn ($vendors) => $vendors->where('market_section', $request->input('section'))));
        $payments = Payment::query()->where('status', 'Paid')->whereNull('reversed_at')
            ->when($request->filled('section'), fn ($query) => $query->whereHas('vendor', fn ($vendors) => $vendors->where('market_section', $request->input('section'))))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('paid_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('paid_at', '<=', $request->input('to')))
            ->orderBy('paid_at')->get(['amount', 'paid_at']);
        $activity = ActivityLog::with('user')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($logs) use ($request): void {
                $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($request->input('search'))).'%';
                $logs->whereRaw("description LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("action LIKE ? ESCAPE '!'", [$term])
                    ->orWhereHas('user', fn ($users) => $users->whereRaw("name LIKE ? ESCAPE '!'", [$term]));
            }))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('to')));

        return view('admin.reports', [
            'vendorCounts' => [
                'total' => (clone $vendors)->count(),
                'active' => (clone $vendors)->where('status', 'Active')->count(),
                'pending' => (clone $vendors)->where('status', 'Pending')->count(),
            ],
            'stallCounts' => [
                'total' => $stalls->count(),
                'occupied' => $stalls->where('status', 'Occupied')->count(),
                'available' => $stalls->where('status', 'Available')->count(),
                'inactive' => $stalls->where('status', 'Inactive')->count(),
            ],
            'paidIncome' => $payments->sum('amount'),
            'outstandingBalance' => (clone $bills)->sum('amount') - (clone $bills)->sum('paid_amount'),
            'sections' => Stall::distinct()->orderBy('market_section')->pluck('market_section'),
            'actions' => ActivityLog::distinct()->orderBy('action')->pluck('action'),
            'sectionStats' => $stalls->groupBy('market_section')->map(fn ($sectionStalls, $section): array => [
                'section' => $section ?: 'Unassigned',
                'total' => $sectionStalls->count(),
                'occupied' => $sectionStalls->where('status', 'Occupied')->count(),
                'available' => $sectionStalls->where('status', 'Available')->count(),
            ])->values(),
            'monthlyIncome' => $payments->groupBy(fn (Payment $payment): string => $payment->paid_at->format('M Y'))
                ->map(fn ($monthPayments): float => (float) $monthPayments->sum('amount')),
            'recentActivity' => $activity->latest()->latest('id')->paginate(10)->withQueryString(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureStallAvailable(Stall $stall, ?int $ignoreRentalId = null): void
    {
        if ($stall->status === 'Inactive') {
            throw ValidationException::withMessages([
                'stall_id' => 'The selected stall is inactive and cannot be rented.',
            ]);
        }

        $activeRentalExists = Rental::query()
            ->active()
            ->where('stall_id', $stall->id)
            ->when($ignoreRentalId, fn ($query) => $query->where('id', '!=', $ignoreRentalId))
            ->exists();

        if ($activeRentalExists) {
            throw ValidationException::withMessages([
                'stall_id' => 'The selected stall already has an active rental.',
            ]);
        }
    }

    private function ensureVendorHasNoActiveRental(Vendor $vendor, ?int $ignoreRentalId = null): void
    {
        $activeRentalExists = Rental::query()
            ->active()
            ->where('vendor_id', $vendor->id)
            ->when($ignoreRentalId, fn ($query) => $query->where('id', '!=', $ignoreRentalId))
            ->exists();

        if ($activeRentalExists) {
            throw ValidationException::withMessages([
                'vendor_id' => 'The selected vendor already has an active rental.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $rentalData
     */
    private function syncVendorFromRental(Vendor $vendor, Stall $stall, array $rentalData): void
    {
        $vendor->update([
            'stall_number' => $stall->stall_number,
            'market_section' => $stall->market_section,
            'monthly_rent' => $rentalData['rent_amount'],
            'billing_cycle' => $rentalData['billing_cycle'],
            'contract_start_date' => $rentalData['start_date'],
            'contract_end_date' => $rentalData['end_date'],
            'contract_until' => $rentalData['end_date'],
            'status' => 'Active',
        ]);
    }

    private function clearVendorAssignmentIfUnoccupied(Vendor $vendor): void
    {
        $activeRental = Rental::query()
            ->active()
            ->where('vendor_id', $vendor->id)
            ->with('stall')
            ->latest('end_date')
            ->first();

        if ($activeRental?->stall) {
            $this->syncVendorFromRental($vendor, $activeRental->stall, [
                'rent_amount' => $activeRental->rent_amount,
                'billing_cycle' => $activeRental->billing_cycle,
                'start_date' => $activeRental->start_date,
                'end_date' => $activeRental->end_date,
            ]);

            return;
        }

        $vendor->update([
            'stall_number' => null,
            'market_section' => null,
            'status' => 'Inactive',
        ]);
    }

    private function recordActivity(string $action, ?object $subject, string $description, array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function announcementListing(Request $request): LengthAwarePaginator
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'in:Published,Draft,Expired,Scheduled'],
            'pinned' => ['nullable', 'in:1,0'],
        ]);
        $query = Announcement::with('user');
        if ($request->filled('search')) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($request->input('search'))).'%';
            $query->where(fn ($posts) => $posts->whereRaw("title LIKE ? ESCAPE '!'", [$term])->orWhereRaw("message LIKE ? ESCAPE '!'", [$term]));
        }
        match ($request->input('visibility')) {
            'Published' => $query->published(),
            'Draft' => $query->whereNull('published_at'),
            'Expired' => $query->whereNotNull('published_at')->where('expires_at', '<=', now()),
            'Scheduled' => $query->where('published_at', '>', now())->where(fn ($posts) => $posts->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            default => null,
        };

        return $query->when($request->filled('pinned'), fn ($posts) => $posts->where('is_pinned', $request->boolean('pinned')))
            ->latest()->latest('id')->paginate(10)->withQueryString();
    }

    private function currentVendor(): ?Vendor
    {
        return Vendor::forUser(auth()->user());
    }
}
