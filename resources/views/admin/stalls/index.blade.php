<x-layouts.admin title="Stall Management" active="stalls">
    <section class="page-heading">
        <div class="flex items-start gap-4">
            <div class="size-12 rounded-xl bg-sky-700 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Stall Management</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">Manage market units, availability, and current vendor assignments across all sectors.</p>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="success-alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="form-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="vendor-summary-grid">
        <article><small>Total stalls</small><strong>{{ $stallCounts->total }}</strong></article>
        <article><small>Occupied</small><strong>{{ $stallCounts->occupied }}</strong></article>
        <article><small>Available</small><strong>{{ $stallCounts->available }}</strong></article>
        <article><small>Inactive</small><strong>{{ $stallCounts->inactive }}</strong></article>
    </section>

    <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
        <label class="app-modal-field min-w-56">
            <span>Search stalls</span>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Number, section, location">
        </label>
        <label class="app-modal-field w-44">
            <span>Status</span>
            <select name="status">
                <option value="">All statuses</option>
                @foreach (['Available', 'Occupied', 'Inactive'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="app-btn-primary">Filter</button>
        <label class="app-modal-field">Section<select name="section"><option value="">All sections</option>@foreach ($sections as $section)<option @selected(request('section') === $section)>{{ $section }}</option>@endforeach</select></label>
        <a href="{{ route('stalls') }}" class="app-btn-cancel">Clear</a>
    </form>

    <div class="vendor-management-actions mt-4 flex items-center gap-3">
        <button type="button" class="app-btn-primary cursor-pointer text-sm font-semibold inline-flex items-center gap-2" onclick="openModal('addStallModal')">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            + Add Stall
        </button>
    </div>

    <section class="vendor-directory-card mt-5">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Stall</th><th>Section</th><th>Type</th><th>Vendor</th><th>Monthly rate</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($stalls as $stall)
                        @php($rental = $stall->rentals->firstWhere('status', 'Active'))
                        <tr>
                            <td><strong>{{ $stall->stall_number }}</strong><small>{{ $stall->location ?: 'Location not set' }}</small></td>
                            <td>{{ $stall->market_section }}</td>
                            <td>{{ $stall->stall_type ?: 'Not specified' }}<small>{{ $stall->dimensions ?: 'Dimensions not set' }}</small></td>
                            <td>{{ $rental?->vendor?->name ?: 'Vacant' }}</td>
                            <td>{{ $stall->monthly_rate !== null ? '₱'.number_format((float) $stall->monthly_rate, 2) : 'Not set' }}</td>
                            <td><span class="vendor-status-pill {{ $stall->status === 'Available' ? 'active' : 'pending' }}">{{ $stall->status }}</span></td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded border border-slate-300 bg-white text-slate-500 transition hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-700"
                                        onclick='openEditStallModal(@json($stall), "{{ addslashes($rental?->vendor?->name ?? 'Vacant') }}")'
                                        data-stall-id="{{ $stall->id }}"
                                        aria-label="Edit stall {{ $stall->stall_number }}"
                                        title="Edit stall"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    @if ($stall->rentals_count === 0)
                                        <button
                                            type="button"
                                            class="inline-flex size-8 items-center justify-center rounded border border-rose-200 bg-white text-rose-700 transition hover:bg-rose-50"
                                            data-delete-url="{{ route('stalls.destroy', $stall) }}"
                                            data-stall-number="{{ $stall->stall_number }}"
                                            aria-label="Delete stall {{ $stall->stall_number }}"
                                            title="Delete stall"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-9 0h12" /></svg>
                                        </button>
                                    @else
                                        <button type="button" disabled class="inline-flex size-8 items-center justify-center rounded border border-slate-200 bg-slate-50 text-slate-300" aria-label="Cannot delete stall {{ $stall->stall_number }} because it has rental history" title="Stalls with rental history cannot be deleted">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-9 0h12" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No stalls have been registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $stalls->links() }}</div>

    {{-- ADD STALL MODAL --}}
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
                            <span>Stall Number</span>
                            <input type="text" id="stall_number_input" value="Auto-generated on save" readonly>
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
                            <span>Length (m) <span class="text-rose-500">*</span></span>
                            <input type="number" name="length_m" id="stall_length_input" min="0.01" max="999999.99" step="0.01" placeholder="e.g. 3" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Width (m) <span class="text-rose-500">*</span></span>
                            <input type="number" name="width_m" id="stall_width_input" min="0.01" max="999999.99" step="0.01" placeholder="e.g. 3" required>
                        </label>
                    </div>

                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Rate per square meter (₱) <span class="text-rose-500">*</span></span>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                <input type="number" name="rate_per_sqm" id="stall_rate_per_sqm_input" min="0" max="99999999.99" step="0.01" class="pl-8" placeholder="Enter rate per m²" required>
                            </div>
                        </label>
                        <label class="app-modal-field">
                            <span>Monthly Rate (₱)</span>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                <input type="number" id="stall_rate_input" class="pl-8" readonly aria-describedby="stall_rate_formula">
                            </div>
                        </label>
                    </div>
                    <p id="stall_rate_formula" class="text-xs text-slate-500">Length × Width = <span id="stall_area_output">0.00</span> m² × Rate per m² = Monthly Rate</p>

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

    {{-- CONFIRM STALL POPUP WINDOW --}}
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

    {{-- EDIT STALL MODAL POP-UP WINDOW --}}
    <div id="editStallModal" class="app-modal-overlay {{ $errors->any() && old('_form') === 'edit_stall_modal' ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="editStallTitle"
        data-old-stall-id="{{ old('_stall_id') }}"
        data-old-stall-number="{{ old('stall_number') }}"
        data-old-market-section="{{ old('market_section') }}"
        data-old-location="{{ old('location') }}"
        data-old-stall-type="{{ old('stall_type') }}"
        data-old-length="{{ old('length_m') }}"
        data-old-width="{{ old('width_m') }}"
        data-old-rate-per-sqm="{{ old('rate_per_sqm') }}"
        data-old-status="{{ old('status') }}">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-sky-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <div>
                        <h2 id="editStallTitle" class="text-base font-bold text-slate-900 m-0">Edit Stall Details</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Modify unit specifications, status, and monthly rental pricing</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('editStallModal')" aria-label="Close edit stall">&times;</button>
            </div>

            <form id="editStallForm" method="POST" data-update-url-template="{{ url('/stalls') }}/__stall__">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_stall_modal">
                <input type="hidden" name="_stall_id" id="edit_stall_id_input" value="{{ old('_stall_id') }}">
                <div class="app-modal-body space-y-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 flex flex-wrap items-center justify-between gap-2">
                        <div>Assigned Vendor: <strong id="edit_stall_vendor" class="text-slate-800">Vacant</strong></div>
                        <div>Floor Area: <strong id="edit_stall_area_display" class="text-slate-800">0.00 m²</strong></div>
                    </div>

                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Stall Number <span class="text-rose-500">*</span></span>
                            <input type="text" name="stall_number" id="edit_stall_number" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Market Section <span class="text-rose-500">*</span></span>
                            <select name="market_section" id="edit_stall_section" required>
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
                            <input type="text" name="location" id="edit_stall_location" placeholder="e.g. Building E, Ground Floor">
                        </label>
                        <label class="app-modal-field">
                            <span>Stall Type</span>
                            <select name="stall_type" id="edit_stall_type">
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
                            <span>Length (m) <span class="text-rose-500">*</span></span>
                            <input type="number" name="length_m" id="edit_stall_length" min="0.01" max="999999.99" step="0.01" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Width (m) <span class="text-rose-500">*</span></span>
                            <input type="number" name="width_m" id="edit_stall_width" min="0.01" max="999999.99" step="0.01" required>
                        </label>
                    </div>

                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Rate per square meter (₱) <span class="text-rose-500">*</span></span>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                <input type="number" name="rate_per_sqm" id="edit_stall_rate_per_sqm" min="0" max="99999999.99" step="0.01" class="pl-8" required>
                            </div>
                        </label>
                        <label class="app-modal-field">
                            <span>Monthly Rate (₱)</span>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                <input type="number" id="edit_stall_rate" class="pl-8" readonly aria-describedby="edit_stall_rate_formula">
                            </div>
                        </label>
                    </div>
                    <p id="edit_stall_rate_formula" class="text-xs text-slate-500">Length × Width = <span id="edit_stall_area_output">0.00</span> m² × Rate per m² = Monthly Rate</p>

                    <label class="app-modal-field">
                        <span>Current Status <span class="text-rose-500">*</span></span>
                        <select name="status" id="edit_stall_status" required>
                            <option value="Available">Available (Ready for allocation)</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Inactive">Inactive (Under maintenance)</option>
                        </select>
                    </label>
                </div>

                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" onclick="closeModal('editStallModal')">Cancel</button>
                    <button type="submit" class="app-btn-primary">Save Changes ✓</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteStallModal" class="app-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="deleteStallTitle">
        <div class="app-modal-panel max-w-md">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-rose-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 003.2 21h17.6a1.6 1.6 0 001.4-2.4L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
                    </div>
                    <div>
                        <h2 id="deleteStallTitle" class="m-0 text-base font-bold text-slate-900">Delete stall?</h2>
                        <p class="m-0 mt-0.5 text-xs text-slate-500">This action cannot be undone.</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeDeleteStallModal()" aria-label="Close delete confirmation">&times;</button>
            </div>

            <div class="app-modal-body">
                <p class="m-0 text-sm text-slate-600">
                    Are you sure you want to permanently delete stall <strong id="deleteStallNumber" class="text-slate-900"></strong>?
                </p>
            </div>

            <form id="deleteStallForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="app-modal-footer sm:justify-end">
                    <button type="button" class="app-btn-cancel" onclick="closeDeleteStallModal()">Cancel</button>
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border-0 bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800">
                        Delete stall
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId)?.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.add('hidden');
        }

        function closeDeleteStallModal() {
            closeModal('deleteStallModal');
        }

        document.querySelectorAll('[data-delete-url]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteStallForm').action = button.dataset.deleteUrl;
                document.getElementById('deleteStallNumber').textContent = button.dataset.stallNumber;
                openModal('deleteStallModal');
            });
        });

        document.getElementById('deleteStallModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeDeleteStallModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDeleteStallModal();
            }
        });

        function openEditStallModal(stall, vendorName) {
            const form = document.getElementById('editStallForm');
            const modal = document.getElementById('editStallModal');
            const isRetry = modal.dataset.oldStallId === String(stall.id);

            form.action = form.dataset.updateUrlTemplate
                ? form.dataset.updateUrlTemplate.replace('__stall__', encodeURIComponent(stall.id))
                : `/stalls/${stall.id}`;
            document.getElementById('edit_stall_id_input').value = stall.id;
            document.getElementById('edit_stall_vendor').textContent = vendorName || 'Vacant';
            document.getElementById('edit_stall_number').value = isRetry ? modal.dataset.oldStallNumber : (stall.stall_number || '');
            document.getElementById('edit_stall_section').value = isRetry ? modal.dataset.oldMarketSection : (stall.market_section || 'Fresh Produce');
            document.getElementById('edit_stall_location').value = isRetry ? modal.dataset.oldLocation : (stall.location || '');
            document.getElementById('edit_stall_type').value = isRetry ? modal.dataset.oldStallType : (stall.stall_type || 'Standard');
            document.getElementById('edit_stall_length').value = isRetry ? modal.dataset.oldLength : (stall.length_m || '');
            document.getElementById('edit_stall_width').value = isRetry ? modal.dataset.oldWidth : (stall.width_m || '');
            document.getElementById('edit_stall_rate_per_sqm').value = isRetry ? modal.dataset.oldRatePerSqm : (stall.rate_per_sqm || '');
            document.getElementById('edit_stall_status').value = isRetry ? modal.dataset.oldStatus : (stall.status || 'Available');
            updateStallRate('edit_stall_length', 'edit_stall_width', 'edit_stall_rate_per_sqm', 'edit_stall_rate', 'edit_stall_area_output');
            openModal('editStallModal');
        }

        function updateStallRate(lengthId, widthId, rateId, totalId, areaId) {
            const length = Number(document.getElementById(lengthId).value);
            const width = Number(document.getElementById(widthId).value);
            const rate = Number(document.getElementById(rateId).value);
            const area = length > 0 && width > 0 ? length * width : 0;
            const monthlyRate = area * rate;

            document.getElementById(areaId).textContent = area.toFixed(2);
            const areaDisplay = document.getElementById('edit_stall_area_display');
            if (areaDisplay && areaId === 'edit_stall_area_output') {
                areaDisplay.textContent = (area > 0 ? area.toFixed(2) : '0.00') + ' m²';
            }
            document.getElementById(totalId).value = area > 0 && document.getElementById(rateId).value !== ''
                ? monthlyRate.toFixed(2)
                : '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            [
                ['stall_length_input', 'stall_width_input', 'stall_rate_per_sqm_input', 'stall_rate_input', 'stall_area_output'],
                ['edit_stall_length', 'edit_stall_width', 'edit_stall_rate_per_sqm', 'edit_stall_rate', 'edit_stall_area_output'],
            ].forEach(function (fields) {
                fields.slice(0, 3).forEach(function (fieldId) {
                    document.getElementById(fieldId).addEventListener('input', function () {
                        updateStallRate(...fields);
                    });
                });
            });

            const editModal = document.getElementById('editStallModal');
            if (editModal && editModal.dataset.oldStallId) {
                const editButton = Array.from(document.querySelectorAll('[data-stall-id]'))
                    .find(button => button.dataset.stallId === editModal.dataset.oldStallId);
                if (editButton) {
                    editButton.click();
                }
            }
        });

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
            const length = Number(document.getElementById('stall_length_input').value);
            const width = Number(document.getElementById('stall_width_input').value);
            const rate = parseFloat(document.getElementById('stall_rate_input').value) || 0;
            const status = document.getElementById('stall_status_input').value;

            document.getElementById('cs_stall_number').textContent = stallNumber;
            document.getElementById('cs_section').textContent = `Section: ${section}`;
            document.getElementById('cs_details').textContent = `Type: ${type} · ${length}m × ${width}m (${(length * width).toFixed(2)} m²)`;
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
