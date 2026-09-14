<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'vendor',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('vendor.dashboard');
    }

    public function dashboard(): View
    {
        return view('dashboard', [
            'vendors' => Vendor::latest()->get(),
            'payments' => Payment::latest('paid_at')->take(4)->get(),
        ]);
    }

    public function vendorDashboard(): View
    {
        return view('vendor-dashboard', [
            'payments' => Payment::where('vendor_name', auth()->user()->name)->latest('paid_at')->get(),
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

        Payment::create($validated);

        return redirect()->route('vendor.dashboard')->with('success', 'Payment submitted successfully.');
    }

    public function createVendor(): View
    {
        return view('vendors.create');
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

        return view('vendors.index', ['vendors' => $vendors]);
    }

    public function stalls(): View
    {
        return view('stalls.index');
    }

    public function rentals(): View
    {
        return view('rentals.index');
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
            'billing_cycle' => ['required', 'in:Monthly,Quarterly'],
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
        Vendor::create($validated);

        return redirect()->route('dashboard')->with('success', 'Vendor added successfully.');
    }

    public function payments(): View
    {
        return view('payments.index', ['payments' => Payment::latest('paid_at')->get(), 'active' => 'payments']);
    }

    public function createPayment(): View
    {
        return view('payments.create', ['vendors' => Vendor::orderBy('name')->get()]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
        ]);

        Payment::create($validated);

        return redirect()->route('payments')->with('success', 'Payment recorded successfully.');
    }

    public function dueDates(): View
    {
        return view('section', ['title' => 'Due Dates', 'description' => 'Keep contracts and payment deadlines on schedule.', 'active' => 'due-dates']);
    }

    public function reports(): View
    {
        return view('section', ['title' => 'Reports', 'description' => 'Review registration, payment, and stall activity.', 'active' => 'reports']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
