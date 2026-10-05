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
                <p class="mt-2 text-sm text-rose-700">The vendor login and profile will be permanently deleted. Payment records remain for audit history.</p>
                <form class="mt-4" method="POST" action="{{ route('vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this vendor account permanently?');">@csrf @method('DELETE')<button class="cursor-pointer rounded bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800" type="submit">Delete vendor</button></form>
            </section>
        </aside>
    </div>
</x-layouts.admin>
