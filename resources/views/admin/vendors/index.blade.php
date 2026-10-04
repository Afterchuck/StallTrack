<x-layouts.admin title="Vendor Management" active="vendors">
    <section class="page-heading">
        <div class="flex items-start gap-4">
            <div class="size-12 rounded-xl bg-emerald-700 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Vendor Management</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">Unified control center for digital vendor records, stall inventory allocation, and active rental leasing contracts across all sectors.</p>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="success-alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="form-alert" role="alert">{{ session('error') }}</div>
    @endif

    <section class="vendor-summary-grid">
        <article><small>Registered vendors</small><strong>{{ $vendors->total() }}</strong></article>
        <article><small>Active vendors</small><strong>{{ $vendors->where('status', 'Active')->count() }}</strong></article>
        <article><small>Accounts awaiting approval</small><strong>{{ $pendingAccountApprovals }}</strong></article>
        <article><small>Monthly rent volume</small><strong>₱{{ number_format($vendors->sum('monthly_rent'), 2) }}</strong></article>
    </section>

    <div class="vendor-management-actions mt-4 flex flex-wrap items-center gap-3">
        <button type="button" class="app-btn-primary cursor-pointer text-sm font-semibold inline-flex items-center gap-2" onclick="openModal('addVendorModal')">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
            + Add Vendor
        </button>
        <button type="button" class="app-btn-secondary cursor-pointer text-sm font-semibold inline-flex items-center gap-2" onclick="openModal('addStallModal')">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            + Add Stall
        </button>
        <a class="vendor-secondary-action inline-flex items-center gap-1.5" href="{{ route('reports') }}">
            <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Export Inventory & Leases
        </a>
    </div>

    <form class="vendor-filter-bar mt-5" method="GET">
        <label class="vendor-search-control">
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Search vendor, stall, or section">
        </label>
        <select name="contract_status">
            <option value="">All statuses</option>
            <option value="Active" @selected(request('contract_status') === 'Active')>Active</option>
            <option value="Pending" @selected(request('contract_status') === 'Pending')>Pending</option>
        </select>
        <button class="vendor-secondary-action" type="submit">Filter</button>
    </form>

    <section class="vendor-directory-card">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Vendor</th><th>Contact</th><th>Stall</th><th>Section</th><th>Rate</th><th>Contract</th><th>Account access</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div>
                                        <strong>{{ $vendor->name }}</strong>
                                        <small>{{ $vendor->market_section ? $vendor->market_section : 'No section assigned' }}</small>
                                    </div>
                                    <a
                                        class="inline-flex shrink-0 items-center rounded border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 no-underline transition hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-700"
                                        href="{{ route('vendors.edit', $vendor) }}"
                                    >
                                        Edit details
                                    </a>
                                </div>
                            </td>
                            <td>{{ $vendor->contact_number ?: 'Not provided' }}</td>
                            <td>{{ $vendor->stall_number ?: 'Pending assignment' }}</td>
                            <td>{{ $vendor->market_section ?: 'Not assigned' }}</td>
                            <td>{{ $vendor->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</td>
                            <td><span class="vendor-status-pill {{ $vendor->status === 'Active' ? 'active' : 'pending' }}">{{ $vendor->status }}</span></td>
                            <td>
                                @if ($vendor->user)
                                    <form method="POST" action="{{ route('vendors.account-access', $vendor) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <select
                                            id="vendor-access-{{ $vendor->id }}"
                                            @class([
                                                'rounded-md border px-2 py-1.5 text-xs font-semibold focus:ring-2',
                                                'border-emerald-300 bg-emerald-50 text-emerald-800 focus:border-emerald-600 focus:ring-emerald-600/20' => $vendor->user->account_approved,
                                                'border-rose-300 bg-rose-50 text-rose-800 focus:border-rose-600 focus:ring-rose-600/20' => ! $vendor->user->account_approved,
                                            ])
                                            name="account_approved"
                                            onchange="this.form.submit()"
                                            aria-label="Set account access for {{ $vendor->name }}"
                                        >
                                            <option value="1" @selected($vendor->user->account_approved)>Approved</option>
                                            <option value="0" @selected(! $vendor->user->account_approved)>Not approved</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="text-slate-400">No login</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a class="vendor-primary-action" href="{{ route('vendors.show', $vendor) }}">Manage account</a>
                                    <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" data-confirm="Delete this vendor and their login? Payment history will be retained.">
                                        @csrf @method('DELETE')
                                        <button class="rounded border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No vendor records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vendor-directory-footer">{{ $vendors->links() }}</div>
    </section>

    {{-- MODAL 1: ADD VENDOR POP-UP WINDOW (Image 2) --}}
    <div id="addVendorModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-emerald-800">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Add Vendor</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Digital enrollment, stall assignment, & contract terms</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('addVendorModal')">&times;</button>
            </div>

            <form id="addVendorForm" method="POST" action="{{ route('vendors.store') }}">
                @csrf
                <div class="app-modal-body">
                    {{-- 1. PERSONAL & TRADE DETAILS --}}
                    <div>
                        <span class="app-modal-section-title">1. Personal & Trade Details</span>
                        <div class="space-y-3">
                            <label class="app-modal-field">
                                <span>Full Name <span class="text-rose-500">*</span></span>
                                <input type="text" name="name" id="vendor_name" placeholder="e.g. Maria Clara Santos" required>
                            </label>

                            <div class="app-modal-grid">
                                <label class="app-modal-field">
                                    <span>Contact Number <span class="text-rose-500">*</span></span>
                                    <input type="text" name="contact_number" id="vendor_contact" placeholder="+63 900 000 0000" required>
                                </label>
                                <label class="app-modal-field">
                                    <span>Business / Product Type <span class="text-rose-500">*</span></span>
                                    <input type="text" name="market_section" id="vendor_section" placeholder="e.g. Organic Greens, Bakery" required>
                                </label>
                            </div>

                            <label class="app-modal-field">
                                <span>Permanent Residential / Business Address <span class="text-rose-500">*</span></span>
                                <textarea name="residential_address" id="vendor_address" rows="2" placeholder="Street, Barangay, City, Province" required></textarea>
                            </label>
                        </div>
                    </div>

                    {{-- 2. STALL ASSIGNMENT & FINANCIAL TERMS --}}
                    <div>
                        <span class="app-modal-section-title">2. Stall Assignment & Financial Terms</span>
                        <div class="space-y-3">
                            <div class="app-modal-grid">
                                <label class="app-modal-field">
                                    <span>Stall Assignment <span class="text-rose-500">*</span></span>
                                    <select name="stall_number" id="vendor_stall_select" required>
                                        <option value="">Select available stall</option>
                                        @forelse ($availableStalls as $stall)
                                            <option value="{{ $stall->stall_number }}"
                                                data-section="{{ $stall->market_section }}"
                                                data-rate="{{ $stall->monthly_rate }}"
                                                data-type="{{ $stall->stall_type }}"
                                                data-dimensions="{{ $stall->dimensions }}"
                                                data-location="{{ $stall->location }}">
                                                {{ $stall->stall_number }} - {{ $stall->market_section }} (₱{{ number_format((float) $stall->monthly_rate, 2) }})
                                            </option>
                                        @empty
                                            <option value="" disabled>No available stalls in inventory</option>
                                        @endforelse
                                    </select>
                                </label>
                                <label class="app-modal-field">
                                    <span>Rental Rate (₱ / cycle) <span class="text-rose-500">*</span></span>
                                    <div class="relative">
                                        <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                        <input type="number" step="0.01" min="0" name="monthly_rent" id="vendor_rent_rate" class="pl-8" placeholder="3500.00" required>
                                    </div>
                                </label>
                            </div>

                            <div class="app-modal-grid">
                                <label class="app-modal-field">
                                    <span>Contract Start Date <span class="text-rose-500">*</span></span>
                                    <input type="date" name="contract_start_date" id="vendor_start_date" value="{{ date('Y-m-d') }}" required>
                                </label>
                                <label class="app-modal-field">
                                    <span>Contract End Date <span class="text-rose-500">*</span></span>
                                    <input type="date" name="contract_end_date" id="vendor_end_date" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required>
                                </label>
                            </div>

                            <div class="space-y-1.5">
                                <span class="text-xs font-semibold text-slate-700 block">Payment Schedule <span class="text-rose-500">*</span></span>
                                <div class="flex items-center gap-6 py-1">
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="radio" name="billing_cycle" value="Monthly" checked class="accent-emerald-700 size-4"> Monthly
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="radio" name="billing_cycle" value="Bi-weekly" class="accent-emerald-700 size-4"> Bi-weekly
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input type="radio" name="billing_cycle" value="Weekly" class="accent-emerald-700 size-4"> Weekly
                                    </label>
                                </div>
                            </div>

                            <div class="app-modal-banner">
                                <span class="text-slate-700 font-medium">Security Deposit Required (2 mos):</span>
                                <strong id="vendor_deposit_display" class="text-emerald-700 font-bold text-sm">₱0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="status" value="Active">

                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" onclick="closeModal('addVendorModal')">Cancel</button>
                    <button type="button" class="app-btn-primary" onclick="proceedToVendorConfirmation()">Save & Generate Lease Agreement</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: CONFIRM VENDOR CONTRACT & LEASE EXECUTION POP-UP WINDOW (Image 3) --}}
    <div id="confirmVendorModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-emerald-800">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Confirm Contract & Lease Execution</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Please review the key terms before finalizing the tenancy agreement and activating billing.</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('confirmVendorModal')">&times;</button>
            </div>

            <div class="app-modal-body space-y-4">
                <div class="app-confirm-card">
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Vendor & Stall Allocation</div>
                        <div class="app-confirm-value">
                            <span id="cv_vendor_name" class="font-bold text-slate-900 block"></span>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span id="cv_stall_pill" class="app-stall-pill"></span>
                                <span id="cv_stall_details" class="text-slate-500 text-xs"></span>
                            </div>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Financial Terms</div>
                        <div class="app-confirm-value">
                            <span id="cv_rate_text" class="font-bold text-slate-900 block"></span>
                            <span id="cv_deposit_text" class="text-slate-500 text-xs block"></span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Lease Tenure</div>
                        <div class="app-confirm-value">
                            <span id="cv_tenure_dates" class="font-bold text-slate-900 block"></span>
                            <span id="cv_duration_text" class="text-slate-500 text-xs block">12 Months Fixed Duration</span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Billing Cycle</div>
                        <div class="app-confirm-value">
                            <span id="cv_billing_cycle" class="text-slate-800 text-xs font-semibold block">Monthly on the 1st of every month</span>
                        </div>
                    </div>
                </div>

                <div class="app-notice-box">
                    <svg class="size-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="m-0 leading-relaxed text-slate-700">
                        <strong>Important Notice:</strong> Confirming will immediately lock stall <span id="cv_notice_stall" class="font-bold text-slate-900"></span> from available market inventory, issue official digital lease agreement <span id="cv_notice_contract" class="font-bold text-emerald-800 font-mono"></span>, and dispatch account activation details to the vendor portal.
                    </p>
                </div>

                <label class="flex items-start gap-2.5 text-xs font-medium text-slate-700 cursor-pointer select-none bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <input type="checkbox" id="cv_agree_checkbox" class="accent-emerald-700 size-4 mt-0.5 shrink-0" onchange="toggleVendorSubmitButton()">
                    <span>I confirm that the vendor credentials and stall specifications have been verified against municipal records.</span>
                </label>
            </div>

            <div class="app-modal-footer">
                <button type="button" class="app-btn-cancel" onclick="backToEditVendor()">Back to Edit</button>
                <button type="button" id="cv_submit_btn" class="app-btn-primary opacity-50 cursor-not-allowed" disabled onclick="executeVendorSubmit()">
                    Confirm & Execute Contract
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL 3: ADD STALL POP-UP WINDOW --}}
    <div id="addStallModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-sky-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Add Stall</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Register new market stall into inventory</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('addStallModal')">&times;</button>
            </div>

            <form id="addStallForm" method="POST" action="{{ route('stalls.store') }}">
                @csrf
                <div class="app-modal-body">
                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Stall Number <span class="text-rose-500">*</span></span>
                            <input type="text" name="stall_number" id="stall_number_input" placeholder="e.g. E-101" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Market Section <span class="text-rose-500">*</span></span>
                            <select name="market_section" id="stall_section_input" required>
                                <option value="">Select market section</option>
                                <option value="Fresh Produce">Fresh Produce</option>
                                <option value="Dry Goods">Dry Goods</option>
                                <option value="Food Court">Food Court</option>
                                <option value="Wet Market">Wet Market</option>
                                <option value="General Merchandise">General Merchandise</option>
                                <option value="Apparel & Footwear">Apparel & Footwear</option>
                            </select>
                        </label>
                    </div>

                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Location / Building</span>
                            <input type="text" name="location" id="stall_location_input" placeholder="e.g. Building E, Ground Floor">
                        </label>
                        <label class="app-modal-field">
                            <span>Stall Type</span>
                            <select name="stall_type" id="stall_type_input">
                                <option value="Standard">Standard</option>
                                <option value="Corner Stall">Corner Stall</option>
                                <option value="Food Stall">Food Stall</option>
                                <option value="Wet Stall">Wet Stall</option>
                                <option value="Kiosk">Kiosk</option>
                            </select>
                        </label>
                    </div>

                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Dimensions</span>
                            <input type="text" name="dimensions" id="stall_dimensions_input" placeholder="e.g. 3m x 3m">
                        </label>
                        <label class="app-modal-field">
                            <span>Monthly Rate (₱) <span class="text-rose-500">*</span></span>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                <input type="number" step="0.01" min="0" name="monthly_rate" id="stall_rate_input" class="pl-8" placeholder="3500.00" required>
                            </div>
                        </label>
                    </div>

                    <label class="app-modal-field">
                        <span>Initial Status <span class="text-rose-500">*</span></span>
                        <select name="status" id="stall_status_input" required>
                            <option value="Available" selected>Available (Ready for allocation)</option>
                            <option value="Inactive">Inactive (Under maintenance)</option>
                            <option value="Occupied">Occupied</option>
                        </select>
                    </label>
                </div>

                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" onclick="closeModal('addStallModal')">Cancel</button>
                    <button type="button" class="app-btn-primary" onclick="proceedToStallConfirmation()">Save & Review Stall</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 4: CONFIRM STALL REGISTRATION POP-UP WINDOW --}}
    <div id="confirmStallModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-sky-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Confirm Stall Registration</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Review stall inventory specifications before adding to database.</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('confirmStallModal')">&times;</button>
            </div>

            <div class="app-modal-body space-y-4">
                <div class="app-confirm-card">
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Stall Identification</div>
                        <div class="app-confirm-value">
                            <span id="cs_stall_number" class="font-bold text-slate-900 block text-base"></span>
                            <span id="cs_section" class="text-slate-500 text-xs block"></span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Specifications</div>
                        <div class="app-confirm-value">
                            <span id="cs_details" class="text-slate-800 text-xs block"></span>
                            <span id="cs_location" class="text-slate-500 text-xs block"></span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Rental Rate</div>
                        <div class="app-confirm-value">
                            <span id="cs_rate" class="font-bold text-emerald-800 block text-sm"></span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Inventory Status</div>
                        <div class="app-confirm-value">
                            <span id="cs_status" class="app-stall-pill"></span>
                        </div>
                    </div>
                </div>

                <div class="app-notice-box">
                    <svg class="size-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="m-0 leading-relaxed text-slate-700">
                        <strong>Important Notice:</strong> Registering will place this stall into the active municipal market database and make it selectable for vendor leasing contracts.
                    </p>
                </div>

                <label class="flex items-start gap-2.5 text-xs font-medium text-slate-700 cursor-pointer select-none bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <input type="checkbox" id="cs_agree_checkbox" class="accent-emerald-700 size-4 mt-0.5 shrink-0" onchange="toggleStallSubmitButton()">
                    <span>I confirm that the stall dimensions and specifications have been verified against architectural blueprints.</span>
                </label>
            </div>

            <div class="app-modal-footer">
                <button type="button" class="app-btn-cancel" onclick="backToEditStall()">Back to Edit</button>
                <button type="button" id="cs_submit_btn" class="app-btn-primary opacity-50 cursor-not-allowed" disabled onclick="executeStallSubmit()">
                    Confirm & Register Stall
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- INTERACTIVE MODAL SCRIPTS --}}
    <script>
        function openModal(modalId) {
            document.getElementById(modalId)?.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.add('hidden');
        }

        // Auto-fill and deposit calculation for Add Vendor
        document.addEventListener('DOMContentLoaded', function () {
            const stallSelect = document.getElementById('vendor_stall_select');
            const rentInput = document.getElementById('vendor_rent_rate');
            const sectionInput = document.getElementById('vendor_section');
            const depositDisplay = document.getElementById('vendor_security_deposit_display');

            function updateDeposit() {
                const rate = parseFloat(rentInput.value) || 0;
                const deposit = rate * 2;
                depositDisplay.textContent = '₱' + deposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            if (stallSelect) {
                stallSelect.addEventListener('change', function () {
                    const opt = this.options[this.selectedIndex];
                    if (opt && opt.dataset) {
                        if (opt.dataset.rate && rentInput) {
                            rentInput.value = parseFloat(opt.dataset.rate).toFixed(2);
                            updateDeposit();
                        }
                        if (opt.dataset.section && sectionInput && !sectionInput.value) {
                            sectionInput.value = opt.dataset.section;
                        }
                    }
                });
            }

            if (rentInput) {
                rentInput.addEventListener('input', updateDeposit);
            }
        });

        // Add Vendor -> Confirmation Flow
        function proceedToVendorConfirmation() {
            const form = document.getElementById('addVendorForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const name = document.getElementById('vendor_name').value;
            const section = document.getElementById('vendor_section').value;
            const stallSelect = document.getElementById('vendor_stall_select');
            const stallOpt = stallSelect.options[stallSelect.selectedIndex];
            const stallNumber = stallSelect.value;
            const rate = parseFloat(document.getElementById('vendor_rent_rate').value) || 0;
            const deposit = rate * 2;
            const startDate = document.getElementById('vendor_start_date').value;
            const endDate = document.getElementById('vendor_end_date').value;
            const billingCycle = document.querySelector('input[name="billing_cycle"]:checked')?.value || 'Monthly';

            // Populate confirmation details
            document.getElementById('cv_vendor_name').textContent = `${name} (${section})`;
            document.getElementById('cv_stall_pill').textContent = `Stall ${stallNumber}`;
            const stallDim = stallOpt.dataset.dimensions ? ` · ${stallOpt.dataset.dimensions}` : '';
            const stallLoc = stallOpt.dataset.location ? ` · ${stallOpt.dataset.location}` : '';
            document.getElementById('cv_stall_details').textContent = `${stallOpt.dataset.section || section}${stallDim}${stallLoc}`;

            const formattedRate = '₱' + rate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const formattedDeposit = '₱' + deposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('cv_rate_text').textContent = `${formattedRate} / month`;
            document.getElementById('cv_deposit_text').textContent = `Security Deposit: ${formattedDeposit} (2 months bond required)`;

            const startFormatted = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const endFormatted = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            document.getElementById('cv_tenure_dates').textContent = `${startFormatted} — ${endFormatted}`;

            document.getElementById('cv_billing_cycle').textContent = `${billingCycle} on the 1st of every cycle`;
            document.getElementById('cv_notice_stall').textContent = stallNumber;
            const year = new Date().getFullYear();
            const cleanStall = stallNumber.replace(/[^A-Za-z0-9]/g, '');
            document.getElementById('cv_notice_contract').textContent = `CTR-${year}-${cleanStall}`;

            // Reset agree checkbox and submit button
            const agreeCheckbox = document.getElementById('cv_agree_checkbox');
            agreeCheckbox.checked = false;
            toggleVendorSubmitButton();

            // Transition modals
            closeModal('addVendorModal');
            openModal('confirmVendorModal');
        }

        function backToEditVendor() {
            closeModal('confirmVendorModal');
            openModal('addVendorModal');
        }

        function toggleVendorSubmitButton() {
            const checked = document.getElementById('cv_agree_checkbox').checked;
            const submitBtn = document.getElementById('cv_submit_btn');
            submitBtn.disabled = !checked;
            if (checked) {
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        function executeVendorSubmit() {
            document.getElementById('addVendorForm').submit();
        }

        // Add Stall -> Confirmation Flow
        function proceedToStallConfirmation() {
            const form = document.getElementById('addStallForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const stallNumber = document.getElementById('stall_number_input').value;
            const section = document.getElementById('stall_section_input').value;
            const location = document.getElementById('stall_location_input').value || 'Location not specified';
            const type = document.getElementById('stall_type_input').value;
            const dimensions = document.getElementById('stall_dimensions_input').value || 'Dimensions standard';
            const rate = parseFloat(document.getElementById('stall_rate_input').value) || 0;
            const status = document.getElementById('stall_status_input').value;

            document.getElementById('cs_stall_number').textContent = `Stall ${stallNumber}`;
            document.getElementById('cs_section').textContent = `Section: ${section}`;
            document.getElementById('cs_details').textContent = `Type: ${type} · ${dimensions}`;
            document.getElementById('cs_location').textContent = `Location: ${location}`;
            document.getElementById('cs_rate').textContent = `₱${rate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} / month`;
            document.getElementById('cs_status').textContent = status;

            const agreeCheckbox = document.getElementById('cs_agree_checkbox');
            agreeCheckbox.checked = false;
            toggleStallSubmitButton();

            closeModal('addStallModal');
            openModal('confirmStallModal');
        }

        function backToEditStall() {
            closeModal('confirmStallModal');
            openModal('addStallModal');
        }

        function toggleStallSubmitButton() {
            const checked = document.getElementById('cs_agree_checkbox').checked;
            const submitBtn = document.getElementById('cs_submit_btn');
            submitBtn.disabled = !checked;
            if (checked) {
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        function executeStallSubmit() {
            document.getElementById('addStallForm').submit();
        }
    </script>
</x-layouts.admin>
