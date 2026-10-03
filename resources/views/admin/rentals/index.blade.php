<x-layouts.admin title="Rental Management" active="rentals">
    <section class="page-heading">
        <div class="flex items-start gap-4">
            <div class="size-12 rounded-xl bg-emerald-800 flex items-center justify-center text-white shrink-0 shadow-sm">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Rental Management</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">Track active leases, rental periods, and official tenancy contract execution across all public market stalls.</p>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="success-alert">{{ session('success') }}</div>
    @endif

    <section class="vendor-summary-grid">
        <article><small>Total contracts</small><strong>{{ $rentals->count() }}</strong></article>
        <article><small>Active contracts</small><strong>{{ $rentals->where('status', 'Active')->count() }}</strong></article>
        <article><small>Expiring soon</small><strong>{{ $rentals->filter(fn ($rental): bool => $rental->status === 'Active' && $rental->end_date->isBetween(today(), today()->addDays(30)))->count() }}</strong></article>
        <article><small>Expired</small><strong>{{ $rentals->where('status', 'Expired')->count() }}</strong></article>
    </section>

    <div class="vendor-management-actions mt-4 flex items-center gap-3">
        <button type="button" class="app-btn-primary cursor-pointer text-sm font-semibold inline-flex items-center gap-2" onclick="openModal('addRentalModal')">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            + New Rental Agreement
        </button>
    </div>

    <section class="vendor-directory-card mt-5">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Contract</th><th>Vendor</th><th>Stall</th><th>Term</th><th>Rent</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($rentals as $rental)
                        @php
                            $isExpiringSoon = $rental->status === 'Active' && $rental->end_date && $rental->end_date->isBetween(today(), today()->addDays(30));
                        @endphp
                        <tr>
                            <td><strong>{{ $rental->contract_number }}</strong></td>
                            <td>{{ $rental->vendor->name }}</td>
                            <td>{{ $rental->stall->stall_number }}</td>
                            <td>{{ $rental->start_date->format('M d, Y') }} – {{ $rental->end_date->format('M d, Y') }}</td>
                            <td>₱{{ number_format((float) $rental->rent_amount, 2) }} / {{ $rental->billing_cycle }}</td>
                            <td class="whitespace-nowrap">
                                @if ($isExpiringSoon)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        <span class="size-1.5 rounded-full bg-indigo-500"></span> Expiring Soon
                                    </span>
                                @elseif ($rental->status === 'Active')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @elseif ($rental->status === 'Expired')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span> Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        <span class="size-1.5 rounded-full bg-slate-400"></span> {{ $rental->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if ($isExpiringSoon)
                                        <button type="button" class="inline-flex items-center gap-1 rounded bg-emerald-800 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-900 transition cursor-pointer border-0" onclick='openEditRentalModal(@json($rental), "renew")'>
                                            Renew
                                        </button>
                                    @elseif ($rental->status === 'Expired')
                                        <button type="button" class="inline-flex items-center gap-1 rounded bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 hover:bg-sky-200 transition cursor-pointer border-0" onclick='openEditRentalModal(@json($rental), "re-lease")'>
                                            Re-lease
                                        </button>
                                    @elseif ($rental->status === 'Active')
                                        <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-900 transition cursor-pointer bg-transparent border-0" onclick='openEditRentalModal(@json($rental))'>
                                            Details
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </button>
                                    @else
                                        <button type="button" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition cursor-pointer border-0" onclick='openEditRentalModal(@json($rental))'>
                                            Archive
                                        </button>
                                    @endif

                                    <button type="button"
                                        title="Edit Contract"
                                        class="inline-flex size-7 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-700 transition cursor-pointer"
                                        onclick='openEditRentalModal(@json($rental))'>
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No rental contracts have been registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- MODAL: CREATE RENTAL CONTRACT POP-UP WINDOW --}}
    <div id="addRentalModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-emerald-800">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Create Rental Contract</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Assign an available stall to a vendor under a formal tenancy agreement</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('addRentalModal')">&times;</button>
            </div>

            <form id="addRentalForm" method="POST" action="{{ route('rentals.store') }}">
                @csrf
                <div class="app-modal-body">
                    <div class="space-y-3">
                        <span class="app-modal-section-title">1. Parties & Stall Assignment</span>
                        <div class="app-modal-grid">
                            <label class="app-modal-field">
                                <span>Vendor <span class="text-rose-500">*</span></span>
                                <select name="vendor_id" id="rental_vendor_select" required>
                                    <option value="">Select vendor</option>
                                    @foreach ($vendors as $v)
                                        <option value="{{ $v->id }}" data-name="{{ $v->name }}" data-section="{{ $v->market_section }}">
                                            {{ $v->name }} ({{ $v->market_section ?: 'Vendor' }})
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="app-modal-field">
                                <span>Stall Assignment <span class="text-rose-500">*</span></span>
                                <select name="stall_id" id="rental_stall_select" required>
                                    <option value="">Select available stall</option>
                                    @forelse ($availableStalls as $st)
                                        <option value="{{ $st->id }}"
                                            data-number="{{ $st->stall_number }}"
                                            data-rate="{{ $st->monthly_rate }}"
                                            data-section="{{ $st->market_section }}"
                                            data-location="{{ $st->location }}"
                                            data-dimensions="{{ $st->dimensions }}">
                                            {{ $st->stall_number }} - {{ $st->market_section }} (₱{{ number_format((float) $st->monthly_rate, 2) }})
                                        </option>
                                    @empty
                                        <option value="" disabled>No available stalls in inventory</option>
                                    @endforelse
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <span class="app-modal-section-title">2. Contract Terms & Rates</span>
                        <div class="app-modal-grid">
                            <label class="app-modal-field">
                                <span>Contract Number <span class="text-rose-500">*</span></span>
                                <input type="text" name="contract_number" id="rental_contract_number" value="CTR-{{ date('Y') }}-{{ rand(1000, 9999) }}" required>
                            </label>
                            <label class="app-modal-field">
                                <span>Rental Rate (₱ / cycle) <span class="text-rose-500">*</span></span>
                                <div class="relative">
                                    <span class="absolute top-1/2 left-3 -translate-y-1/2 text-slate-400 font-semibold text-sm">₱</span>
                                    <input type="number" step="0.01" min="0" name="rent_amount" id="rental_rent_amount" class="pl-8" placeholder="3500.00" required>
                                </div>
                            </label>
                        </div>

                        <div class="app-modal-grid">
                            <label class="app-modal-field">
                                <span>Billing Cycle <span class="text-rose-500">*</span></span>
                                <select name="billing_cycle" id="rental_billing_cycle" required>
                                    <option value="Monthly" selected>Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                    <option value="Bi-weekly">Bi-weekly</option>
                                    <option value="Weekly">Weekly</option>
                                </select>
                            </label>
                            <label class="app-modal-field">
                                <span>Contract Status <span class="text-rose-500">*</span></span>
                                <select name="status" id="rental_status" required>
                                    <option value="Active" selected>Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </label>
                        </div>

                        <div class="app-modal-grid">
                            <label class="app-modal-field">
                                <span>Start Date <span class="text-rose-500">*</span></span>
                                <input type="date" name="start_date" id="rental_start_date" value="{{ date('Y-m-d') }}" required>
                            </label>
                            <label class="app-modal-field">
                                <span>End Date <span class="text-rose-500">*</span></span>
                                <input type="date" name="end_date" id="rental_end_date" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required>
                            </label>
                        </div>

                        <div class="app-modal-banner">
                            <span class="text-slate-700 font-medium">Security Deposit Required (2 mos):</span>
                            <strong id="rental_deposit_display" class="text-emerald-700 font-bold text-sm">₱0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" onclick="closeModal('addRentalModal')">Cancel</button>
                    <button type="button" class="app-btn-primary" onclick="proceedToRentalConfirmation()">Save & Review Contract</button>
                </div>
            </form>
        </div>
    </div>

    {{-- CONFIRM RENTAL CONTRACT POPUP WINDOW --}}
    <div id="confirmRentalModal" class="app-modal-overlay hidden">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-emerald-800">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 m-0">Confirm Contract & Lease Execution</h2>
                        <p class="text-xs text-slate-500 m-0 mt-0.5">Review tenancy agreement terms before committing contract to database.</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('confirmRentalModal')">&times;</button>
            </div>

            <div class="app-modal-body space-y-4">
                <div class="app-confirm-card">
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Vendor & Stall Allocation</div>
                        <div class="app-confirm-value">
                            <span id="cr_vendor_name" class="font-bold text-slate-900 block text-sm"></span>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span id="cr_stall_pill" class="app-stall-pill"></span>
                                <span id="cr_stall_details" class="text-slate-500 text-xs"></span>
                            </div>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Financial Terms</div>
                        <div class="app-confirm-value">
                            <span id="cr_rate_text" class="font-bold text-slate-900 block text-sm"></span>
                            <span id="cr_deposit_text" class="text-slate-500 text-xs block"></span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Lease Tenure</div>
                        <div class="app-confirm-value">
                            <span id="cr_tenure_dates" class="font-bold text-slate-900 block text-sm"></span>
                            <span id="cr_duration_text" class="text-slate-500 text-xs block">12 Months Fixed Duration</span>
                        </div>
                    </div>
                    <div class="app-confirm-row">
                        <div class="app-confirm-label">Contract Reference</div>
                        <div class="app-confirm-value">
                            <span id="cr_contract_number" class="font-mono text-emerald-800 font-bold block text-sm"></span>
                            <span id="cr_cycle_text" class="text-slate-500 text-xs block"></span>
                        </div>
                    </div>
                </div>

                <div class="app-notice-box">
                    <svg class="size-5 text-sky-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="m-0 leading-relaxed text-slate-700">
                        <strong>Important Notice:</strong> Confirming will immediately mark the selected stall as <strong>Occupied</strong>, lock it from the available pool, and record an active rental contract.
                    </p>
                </div>

                <label class="flex items-start gap-2.5 text-xs font-medium text-slate-700 cursor-pointer select-none bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <input type="checkbox" id="cr_agree_checkbox" class="accent-emerald-700 size-4 mt-0.5 shrink-0" onchange="toggleRentalSubmitButton()">
                    <span>I confirm that the tenancy terms, vendor credentials, and stall specifications have been verified against municipal records.</span>
                </label>
            </div>

            <div class="app-modal-footer">
                <button type="button" class="app-btn-cancel" onclick="backToEditRental()">Back to Edit</button>
                <button type="button" id="cr_submit_btn" class="app-btn-primary opacity-50 cursor-not-allowed" disabled onclick="executeRentalSubmit()">
                    Confirm & Execute Contract
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId)?.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const stallSelect = document.getElementById('rental_stall_select');
            const rentInput = document.getElementById('rental_rent_amount');
            const depositDisplay = document.getElementById('rental_deposit_display');
            const contractInput = document.getElementById('rental_contract_number');

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
                        if (opt.dataset.number && contractInput) {
                            const year = new Date().getFullYear();
                            const clean = opt.dataset.number.replace(/[^A-Za-z0-9]/g, '');
                            contractInput.value = `CTR-${year}-${clean}`;
                        }
                    }
                });
            }

            if (rentInput) {
                rentInput.addEventListener('input', updateDeposit);
            }
        });

        function proceedToRentalConfirmation() {
            const form = document.getElementById('addRentalForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const vendorSelect = document.getElementById('rental_vendor_select');
            const vendorOpt = vendorSelect.options[vendorSelect.selectedIndex];
            const vendorName = vendorOpt.dataset.name || vendorOpt.text;

            const stallSelect = document.getElementById('rental_stall_select');
            const stallOpt = stallSelect.options[stallSelect.selectedIndex];
            const stallNumber = stallOpt.dataset.number || 'Stall';
            const stallDetails = `${stallOpt.dataset.section || ''} · ${stallOpt.dataset.dimensions || ''} · ${stallOpt.dataset.location || ''}`;

            const rate = parseFloat(document.getElementById('rental_rent_amount').value) || 0;
            const deposit = rate * 2;
            const contractNumber = document.getElementById('rental_contract_number').value;
            const cycle = document.getElementById('rental_billing_cycle').value;
            const startDate = document.getElementById('rental_start_date').value;
            const endDate = document.getElementById('rental_end_date').value;

            document.getElementById('cr_vendor_name').textContent = vendorName;
            document.getElementById('cr_stall_pill').textContent = `Stall ${stallNumber}`;
            document.getElementById('cr_stall_details').textContent = stallDetails;

            const formattedRate = '₱' + rate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const formattedDeposit = '₱' + deposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('cr_rate_text').textContent = `${formattedRate} / ${cycle}`;
            document.getElementById('cr_deposit_text').textContent = `Security Deposit: ${formattedDeposit} (2 months bond required)`;

            const startFormatted = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const endFormatted = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            document.getElementById('cr_tenure_dates').textContent = `${startFormatted} — ${endFormatted}`;
            document.getElementById('cr_contract_number').textContent = contractNumber;
            document.getElementById('cr_cycle_text').textContent = `Billing Schedule: ${cycle}`;

            const agreeCheckbox = document.getElementById('cr_agree_checkbox');
            agreeCheckbox.checked = false;
            toggleRentalSubmitButton();

            closeModal('addRentalModal');
            openModal('confirmRentalModal');
        }

        function backToEditRental() {
            closeModal('confirmRentalModal');
            openModal('addRentalModal');
        }

        function toggleRentalSubmitButton() {
            const checked = document.getElementById('cr_agree_checkbox').checked;
            const submitBtn = document.getElementById('cr_submit_btn');
            submitBtn.disabled = !checked;
            if (checked) {
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        function executeRentalSubmit() {
            document.getElementById('addRentalForm').submit();
        }
    </script>
</x-layouts.admin>
