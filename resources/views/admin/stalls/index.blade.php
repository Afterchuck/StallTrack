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
    @if (session('error'))
        <div class="mb-4 rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ session('error') }}</div>
    @endif

    <section class="vendor-summary-grid">
        <article><small>Total stalls</small><strong>{{ $stalls->count() }}</strong></article>
        <article><small>Occupied</small><strong>{{ $stalls->where('status', 'Occupied')->count() }}</strong></article>
        <article><small>Available</small><strong>{{ $stalls->where('status', 'Available')->count() }}</strong></article>
        <article><small>Inactive</small><strong>{{ $stalls->where('status', 'Inactive')->count() }}</strong></article>
    </section>

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
                                <button type="button"
                                    class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-700 transition cursor-pointer"
                                    onclick='openEditStallModal(@json($stall))'>
                                    <svg class="size-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    Edit
                                </button>
                                <form class="inline" method="POST" action="{{ route('stalls.destroy', $stall) }}" data-confirm="Delete this stall? Stalls linked to rental contracts cannot be deleted.">
                                    @csrf @method('DELETE')
                                    <button class="ml-1 rounded border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No stalls have been registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

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
    <div id="editStallModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-sky-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Edit Stall Details</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Modify unit specifications, status, and monthly rental pricing</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('editStallModal')">&times;</button>
            </div>

            <form id="editStallForm" method="POST">
                @csrf
                @method('PUT')
                <div class="app-modal-body">
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
                            <span>Dimensions</span>
                            <input type="text" name="dimensions" id="edit_stall_dimensions" placeholder="e.g. 3m x 3m">
                        </label>
                        <label class="app-modal-field">
                            <span>Monthly Rate (₱) <span class="text-rose-500">*</span></span>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                <input type="number" step="0.01" min="0" name="monthly_rate" id="edit_stall_rate" class="pl-8" required>
                            </div>
                        </label>
                    </div>

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

    <script>
        function openModal(modalId) {
            document.getElementById(modalId)?.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.add('hidden');
        }

        function openEditStallModal(stall) {
            document.getElementById('editStallForm').action = `/stalls/${stall.id}`;
            document.getElementById('edit_stall_number').value = stall.stall_number || '';
            document.getElementById('edit_stall_section').value = stall.market_section || 'Fresh Produce';
            document.getElementById('edit_stall_location').value = stall.location || '';
            document.getElementById('edit_stall_type').value = stall.stall_type || 'Standard';
            document.getElementById('edit_stall_dimensions').value = stall.dimensions || '';
            document.getElementById('edit_stall_rate').value = parseFloat(stall.monthly_rate || 0).toFixed(2);
            document.getElementById('edit_stall_status').value = stall.status || 'Available';
            openModal('editStallModal');
        }

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
