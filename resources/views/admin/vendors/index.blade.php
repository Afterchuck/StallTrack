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
    @if ($errors->any())
        <div class="form-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="vendor-summary-grid">
        <article><small>Registered vendors</small><strong>{{ $vendors->total() }}</strong></article>
        <article><small>Active vendors</small><strong>{{ $vendors->where('status', 'Active')->count() }}</strong></article>
        <article><small>Pending applications</small><strong>{{ $pendingApplications }}</strong></article>
        <article><small>Monthly rent volume</small><strong>₱{{ number_format($vendors->sum('monthly_rent'), 2) }}</strong></article>
    </section>

    <div class="vendor-management-actions mt-4 flex flex-wrap items-center gap-3">
        <a class="vendor-secondary-action inline-flex items-center gap-1.5" href="{{ route('vendors.export', request()->query()) }}">
            <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Export Inventory & Leases
        </a>
    </div>

    <form class="vendor-filter-bar mt-5" method="GET">
        <label class="vendor-search-control">
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Search vendor, stall, or section">
        </label>
        <select name="contract_status" aria-label="Vendor status">
            <option value="">All statuses</option>
            <option value="Active" @selected(request('contract_status') === 'Active')>Active</option>
            <option value="Pending" @selected(request('contract_status') === 'Pending')>Pending</option>
            <option value="Inactive" @selected(request('contract_status') === 'Inactive')>Inactive</option>
        </select>
        <label class="vendor-section-filter">Section<select name="stall_type" aria-label="Market section"><option value="">All sections</option>@foreach ($sections as $section)<option value="{{ $section }}" @selected(request('stall_type') === $section)>{{ $section }}</option>@endforeach</select></label>
        <button class="vendor-secondary-action cursor-pointer" type="submit">Filter</button>
        <a class="vendor-filter-reset" href="{{ route('vendors.index') }}">Reset</a>
    </form>

    <section class="vendor-directory-card">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Vendor</th><th>Contact</th><th>Stall</th><th>Section</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div>
                                        <strong>{{ $vendor->name }}</strong>
                                        <small>{{ $vendor->market_section ? $vendor->market_section : 'No section assigned' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $vendor->contact_number ?: 'Not provided' }}</td>
                            <td>{{ $vendor->stall_number ?: 'Pending assignment' }}</td>
                            <td>{{ $vendor->market_section ?: 'Not assigned' }}</td>
                            <td>{{ $vendor->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</td>
                            <td>
                                @if ($vendor->user?->role === 'vendor')
                                    <form method="POST" action="{{ route('vendors.approval.update', $vendor) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="approval_status_{{ $vendor->id }}">Account approval for {{ $vendor->name }}</label>
                                        <select id="approval_status_{{ $vendor->id }}" name="approval_status" class="vendor-approval-select">
                                            <option value="Pending" @selected($vendor->approval_status === 'Pending')>Pending</option>
                                            <option value="Approved" @selected($vendor->approval_status === 'Approved')>Approved</option>
                                            <option value="Not approved" @selected($vendor->approval_status === 'Not approved')>Not approved</option>
                                        </select>
                                        <button class="min-h-8 rounded bg-slate-900 px-3 py-1 text-xs font-semibold text-white transition hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2" type="submit">Save</button>
                                    </form>
                                @else
                                    <span class="vendor-status-pill {{ $vendor->status === 'Active' ? 'active' : 'pending' }}">{{ $vendor->status }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded border border-slate-300 bg-white text-slate-500 hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-700"
                                        onclick="openEditVendorModal(this)"
                                        data-vendor-id="{{ $vendor->id }}"
                                        data-vendor-name="{{ $vendor->name }}"
                                        data-vendor-email="{{ $vendor->email }}"
                                        data-vendor-contact-number="{{ $vendor->contact_number }}"
                                        data-vendor-residential-address="{{ $vendor->residential_address }}"
                                        data-vendor-stall-number="{{ $vendor->stall_number }}"
                                        data-vendor-market-section="{{ $vendor->market_section }}"
                                        data-vendor-monthly-rent="{{ $vendor->monthly_rent }}"
                                        data-vendor-billing-cycle="{{ $vendor->billing_cycle }}"
                                        data-vendor-contract-start-date="{{ $vendor->contract_start_date?->toDateString() }}"
                                        data-vendor-contract-end-date="{{ $vendor->contract_end_date?->toDateString() }}"
                                        data-vendor-status="{{ $vendor->status }}"
                                        aria-label="Edit {{ $vendor->name }}"
                                        title="Edit vendor"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    @if ($vendor->stall_number === null && $vendor->bills_count === 0 && $vendor->payments_count === 0 && $vendor->rentals_count === 0)
                                        <button type="button"
                                            class="inline-flex size-8 items-center justify-center rounded border border-rose-200 bg-white text-rose-700 transition hover:bg-rose-50"
                                            data-delete-url="{{ route('vendors.destroy', $vendor) }}"
                                            data-vendor-name="{{ $vendor->name }}"
                                            aria-label="Delete vendor {{ $vendor->name }}"
                                            title="Delete vendor">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-9 0h12" /></svg>
                                        </button>
                                    @else
                                        <button type="button" disabled class="inline-flex size-8 items-center justify-center rounded border border-slate-200 bg-slate-50 text-slate-300" aria-label="Cannot delete vendor {{ $vendor->name }} because it is linked to a stall or has rental or payment history" title="Unassign the vendor from any stall and retain accounts with rental or payment history">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-9 0h12" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No vendor records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vendor-directory-footer">{{ $vendors->links() }}</div>
    </section>

    <div id="editVendorModal" class="app-modal-overlay {{ $errors->any() && old('_form') === 'edit_vendor_modal' ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="editVendorTitle"
        data-old-vendor-id="{{ old('_vendor_id') }}"
        data-old-name="{{ old('name') }}"
        data-old-email="{{ old('email') }}"
        data-old-contact-number="{{ old('contact_number') }}"
        data-old-residential-address="{{ old('residential_address') }}"
        data-old-stall-number="{{ old('stall_number') }}"
        data-old-market-section="{{ old('market_section') }}"
        data-old-monthly-rent="{{ old('monthly_rent') }}"
        data-old-billing-cycle="{{ old('billing_cycle') }}"
        data-old-contract-start-date="{{ old('contract_start_date') }}"
        data-old-contract-end-date="{{ old('contract_end_date') }}"
        data-old-status="{{ old('status') }}">
        <div class="app-modal-panel">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-emerald-800">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828z" /></svg>
                    </div>
                    <div>
                        <h2 id="editVendorTitle" class="m-0 text-base font-bold text-slate-900">Edit Vendor</h2>
                        <p class="m-0 mt-0.5 text-xs text-slate-500">Update vendor details and linked stall and rental information.</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('editVendorModal')" aria-label="Close edit vendor">&times;</button>
            </div>

            <form id="editVendorForm" method="POST" data-update-url-template="{{ route('vendors.update', ['vendor' => '__vendor__']) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_vendor_modal">
                <input type="hidden" name="_vendor_id" id="edit_vendor_id">
                <div class="app-modal-body">
                    @if ($errors->any() && old('_form') === 'edit_vendor_modal')
                        <div class="form-alert" role="alert">
                            <strong>Please correct the following:</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Full Name <span class="text-rose-500">*</span></span>
                            <input type="text" name="name" id="edit_vendor_name" maxlength="255" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Email</span>
                            <input type="email" name="email" id="edit_vendor_email" maxlength="255">
                        </label>
                    </div>
                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Contact Number</span>
                            <input type="text" name="contact_number" id="edit_vendor_contact_number" maxlength="30">
                        </label>
                        <label class="app-modal-field">
                            <span>Status <span class="text-rose-500">*</span></span>
                            <select name="status" id="edit_vendor_status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </label>
                    </div>
                    <label class="app-modal-field">
                        <span>Residential Address</span>
                        <textarea name="residential_address" id="edit_vendor_residential_address" rows="2" maxlength="1000"></textarea>
                    </label>
                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Linked Stall <span class="text-rose-500">*</span></span>
                            <select name="stall_number" id="edit_vendor_stall_number" required>
                                @foreach ($editableStalls as $stall)
                                    <option value="{{ $stall->stall_number }}" data-status="{{ $stall->status }}" data-section="{{ $stall->market_section }}" data-rate="{{ $stall->monthly_rate }}">
                                        {{ $stall->stall_number }} - {{ $stall->market_section }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="app-modal-field">
                            <span>Market Section <span class="text-rose-500">*</span></span>
                            <input type="text" name="market_section" id="edit_vendor_market_section" maxlength="100" required>
                        </label>
                    </div>
                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Rental Rate (₱) <span class="text-rose-500">*</span></span>
                            <input type="number" name="monthly_rent" id="edit_vendor_monthly_rent" min="0" step="0.01" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Billing Cycle <span class="text-rose-500">*</span></span>
                            <select name="billing_cycle" id="edit_vendor_billing_cycle" required>
                                @foreach (['Monthly', 'Quarterly', 'Bi-weekly', 'Weekly'] as $cycle)
                                    <option value="{{ $cycle }}">{{ $cycle }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="app-modal-grid">
                        <label class="app-modal-field">
                            <span>Contract Start <span class="text-rose-500">*</span></span>
                            <input type="date" name="contract_start_date" id="edit_vendor_contract_start_date" required>
                        </label>
                        <label class="app-modal-field">
                            <span>Contract End <span class="text-rose-500">*</span></span>
                            <input type="date" name="contract_end_date" id="edit_vendor_contract_end_date" required>
                        </label>
                    </div>
                </div>
                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" onclick="closeModal('editVendorModal')">Cancel</button>
                    <button type="submit" class="app-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteVendorModal" class="app-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="deleteVendorTitle">
        <div class="app-modal-panel max-w-md">
            <div class="app-modal-header">
                <div class="flex items-center gap-3">
                    <div class="app-modal-icon bg-rose-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 003.2 21h17.6a1.6 1.6 0 001.4-2.4L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
                    </div>
                    <div>
                        <h2 id="deleteVendorTitle" class="m-0 text-base font-bold text-slate-900">Delete vendor?</h2>
                        <p class="m-0 mt-0.5 text-xs text-slate-500">This action cannot be undone.</p>
                    </div>
                </div>
                <button type="button" class="app-modal-close" onclick="closeModal('deleteVendorModal')" aria-label="Close delete confirmation">&times;</button>
            </div>
            <div class="app-modal-body">
                <p class="m-0 text-sm text-slate-600">
                    Permanently delete <strong id="deleteVendorName" class="text-slate-900"></strong>'s vendor profile and login?
                </p>
            </div>
            <form id="deleteVendorForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="app-modal-footer sm:justify-end">
                    <button type="button" class="app-btn-cancel" onclick="closeModal('deleteVendorModal')">Cancel</button>
                    <button type="submit" class="min-h-11 cursor-pointer rounded-lg bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800">
                        Delete vendor
                    </button>
                </div>
            </form>
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

        function openEditVendorModal(button) {
            const modal = document.getElementById('editVendorModal');
            const form = document.getElementById('editVendorForm');
            const isRetry = modal.dataset.oldVendorId === button.dataset.vendorId;
            const fields = [
                ['name', 'vendorName', 'oldName'],
                ['email', 'vendorEmail', 'oldEmail'],
                ['contact_number', 'vendorContactNumber', 'oldContactNumber'],
                ['residential_address', 'vendorResidentialAddress', 'oldResidentialAddress'],
                ['stall_number', 'vendorStallNumber', 'oldStallNumber'],
                ['market_section', 'vendorMarketSection', 'oldMarketSection'],
                ['monthly_rent', 'vendorMonthlyRent', 'oldMonthlyRent'],
                ['billing_cycle', 'vendorBillingCycle', 'oldBillingCycle'],
                ['contract_start_date', 'vendorContractStartDate', 'oldContractStartDate'],
                ['contract_end_date', 'vendorContractEndDate', 'oldContractEndDate'],
                ['status', 'vendorStatus', 'oldStatus'],
            ];

            form.action = form.dataset.updateUrlTemplate.replace('__vendor__', encodeURIComponent(button.dataset.vendorId));
            document.getElementById('edit_vendor_id').value = button.dataset.vendorId;
            fields.forEach(([field, vendorData, oldData]) => {
                document.getElementById(`edit_vendor_${field}`).value = isRetry
                    ? modal.dataset[oldData]
                    : button.dataset[vendorData] || '';
            });

            const stallSelect = document.getElementById('edit_vendor_stall_number');
            Array.from(stallSelect.options).forEach(option => {
                option.disabled = option.dataset.status !== 'Available' && option.value !== button.dataset.vendorStallNumber;
            });
            stallSelect.onchange = function () {
                const selectedOption = this.options[this.selectedIndex];
                document.getElementById('edit_vendor_market_section').value = selectedOption.dataset.section || '';
                document.getElementById('edit_vendor_monthly_rent').value = selectedOption.dataset.rate || '';
            };

            openModal('editVendorModal');
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-delete-url]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('deleteVendorForm').action = button.dataset.deleteUrl;
                    document.getElementById('deleteVendorName').textContent = button.dataset.vendorName;
                    openModal('deleteVendorModal');
                });
            });

            const editModal = document.getElementById('editVendorModal');
            if (editModal.dataset.oldVendorId) {
                const editButton = Array.from(document.querySelectorAll('[data-vendor-id]'))
                    .find(button => button.dataset.vendorId === editModal.dataset.oldVendorId);
                if (editButton) {
                    openEditVendorModal(editButton);
                }
            }
        });
    </script>
</x-layouts.admin>
