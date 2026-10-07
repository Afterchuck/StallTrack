<x-layouts.admin title="Vendor Account" active="vendors">
    <div class="form-back"><a href="{{ route('vendors.index') }}">← Back to vendors</a></div>
    <section class="page-heading">
        <div>
            <h1>{{ $vendor->name }}</h1>
            <p>Manage vendor details, linked stall assignment, and in-person payment records.</p>
        </div>
        <a class="accent-button" href="{{ route('vendors.edit', $vendor) }}">Edit vendor details</a>
    </section>

    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    @if (session('success'))<div class="success-alert">{{ session('success') }}</div>@endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="grid content-start gap-6">
            <section class="dashboard-panel">
                <div class="panel-title"><div><h2>Vendor &amp; stall details</h2><p>Current official account details.</p></div><span class="vendor-status-pill {{ $vendor->status === 'Active' ? 'active' : 'pending' }}">{{ $vendor->status }}</span></div>
                <dl class="grid gap-5 text-sm md:grid-cols-2">
                    <div><dt class="text-slate-500">Contact number</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor->contact_number ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-slate-500">Email</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor->email ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-slate-500">Linked stall</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor->stall_number }}</dd></div>
                    <div><dt class="text-slate-500">Section</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor->market_section }}</dd></div>
                    <div><dt class="text-slate-500">Rental rate</dt><dd class="mt-1 font-semibold text-slate-800">₱{{ number_format((float) $vendor->monthly_rent, 2) }} / {{ strtolower($vendor->billing_cycle) }}</dd></div>
                    <div><dt class="text-slate-500">Contract period</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor->contract_start_date?->format('M d, Y') }} – {{ $vendor->contract_end_date?->format('M d, Y') }}</dd></div>
                    <div class="md:col-span-2"><dt class="text-slate-500">Address</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor->residential_address ?: 'Not provided' }}</dd></div>
                </dl>
            </section>

            <section class="dashboard-panel">
                <div class="panel-title"><div><h2>Payment history</h2><p>Payments below are visible in this vendor's Billing &amp; Payment History.</p></div></div>
                <div class="dashboard-table-wrap"><table class="dashboard-table"><thead><tr><th>Receipt</th><th>Date</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ $payment->receipt_number }}</td><td>{{ $payment->paid_at->format('M d, Y') }}</td><td>₱{{ number_format((float) $payment->amount, 2) }}</td><td><span class="table-status {{ $payment->status === 'Paid' ? 'upcoming' : 'pending' }}">{{ $payment->status }}</span></td><td>@if ($payment->bill_id)<a href="{{ route('bills.show', $payment->bill_id) }}">View bill</a>@elseif ($payment->status === 'Reversed')<span>Reversed</span>@elseif ($payment->status === 'Paid')<span class="text-sm font-semibold text-slate-500">Paid</span>@else<form method="POST" action="{{ route('vendors.payments.paid', [$vendor, $payment]) }}">@csrf @method('PATCH')<button class="black-button min-h-0 px-3 py-1.5" type="submit">Paid</button></form>@endif</td></tr>@empty<tr><td colspan="5">No payments have been recorded.</td></tr>@endforelse</tbody></table></div>
            </section>
        </div>

        <aside class="grid content-start gap-6">
            <section class="form-panel max-w-none">
                <h2 class="m-0 text-lg font-semibold">Record payment</h2>
                <p class="mt-1 text-sm text-slate-500">This payment will appear in {{ $vendor->name }}'s vendor portal.</p>
                <a class="app-btn-primary mt-5 inline-block" href="{{ route('payments', ['vendor_id' => $vendor->id]) }}">View bills / record payment</a>
                <p class="text-sm text-slate-500">Choose a bill to record a full or partial payment. Existing receipts can be applied to the correct billing period.</p>
            </section>

            <section class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                <h2 class="m-0 text-base font-semibold text-rose-800">Delete vendor account</h2>
                @if ($hasLinkedStall)
                    <p class="mt-2 text-sm text-rose-700">This vendor is linked to stall {{ $vendor->stall_number ?: 'through an active rental' }}. Unassign the vendor from the stall before deleting the account.</p>
                    <button class="mt-4 cursor-not-allowed rounded bg-rose-300 px-4 py-2 text-sm font-semibold text-white" type="button" disabled aria-describedby="vendor-delete-disabled">
                        Delete vendor
                    </button>
                    <p id="vendor-delete-disabled" class="mt-2 text-xs font-medium text-rose-700">Deletion is disabled while a stall is assigned.</p>
                @elseif ($hasDeletionHistory)
                    <p class="mt-2 text-sm text-rose-700">This vendor has rental or payment history and cannot be deleted. Set the vendor to inactive instead.</p>
                    <button class="mt-4 cursor-not-allowed rounded bg-rose-300 px-4 py-2 text-sm font-semibold text-white" type="button" disabled>
                        Delete vendor
                    </button>
                @else
                    <p class="mt-2 text-sm text-rose-700">The vendor login and profile will be permanently deleted. This action cannot be undone.</p>
                    <button type="button" class="mt-4 cursor-pointer rounded bg-rose-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800" onclick="openModal('deleteVendorModal')">
                        Delete vendor
                    </button>
                @endif
            </section>
        </aside>
    </div>

    @unless ($hasLinkedStall || $hasDeletionHistory)
        <div id="deleteVendorModal" class="app-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="deleteVendorTitle">
            <div class="app-modal-panel max-w-md">
                <div class="app-modal-header">
                    <div class="flex items-center gap-3">
                        <div class="app-modal-icon bg-rose-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 003.2 21h17.6a1.6 1.6 0 001.4-2.4L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
                        </div>
                        <div>
                            <h2 id="deleteVendorTitle" class="m-0 text-base font-bold text-slate-900">Delete vendor account?</h2>
                            <p class="m-0 mt-0.5 text-xs text-slate-500">This action cannot be undone.</p>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" onclick="closeModal('deleteVendorModal')" aria-label="Close delete confirmation">&times;</button>
                </div>

                <div class="app-modal-body">
                    <p class="m-0 text-sm text-slate-600">
                        Permanently delete <strong class="text-slate-900">{{ $vendor->name }}</strong>'s vendor profile and login?
                    </p>
                </div>

                <form method="POST" action="{{ route('vendors.destroy', $vendor) }}">
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
    @endunless
</x-layouts.admin>
