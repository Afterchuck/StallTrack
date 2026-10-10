<x-layouts.admin title="Rental billing" active="payments">
    <section class="page-heading"><h1>Rental billing</h1><p>Review each vendor’s rented stall and send the rent already agreed in their contract. No meter readings or manual rent entry.</p></section>
    @if (session('success'))<div class="success-alert" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <div class="mb-5 flex flex-wrap gap-3">
        <a class="app-btn-primary" href="{{ route('payments') }}">View bills &amp; record payments</a>
        <a class="app-btn-secondary" href="{{ route('rentals') }}">Manage rental contracts</a>
    </div>
    <section class="dashboard-panel">
        <form method="GET" action="{{ route('rental-billing') }}" class="mb-5 flex flex-wrap items-end gap-4">
            <label class="field">Search rentals<input type="search" name="search" maxlength="100" value="{{ request('search') }}" placeholder="Vendor, email, stall, contract, section"></label>
            <label class="field">Billing cycle<select name="cycle"><option value="">All cycles</option>@foreach (['Monthly', 'Quarterly', 'Weekly', 'Bi-weekly'] as $cycle)<option @selected(request('cycle') === $cycle)>{{ $cycle }}</option>@endforeach</select></label>
            <label class="field">Date within billing period<input type="date" name="billing_date" value="{{ $billingDate }}" required></label>
            <label class="field">Vendor<select name="vendor_id"><option value="">All vendors</option>@foreach ($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></label>
            <button type="submit" class="app-btn-primary">Show rentals</button>
            <a href="{{ route('rental-billing') }}">Reset</a>
        </form>
        <p class="mb-4 text-sm text-slate-500">Periods follow each contract’s start date and billing cycle. “Ready to review” is not yet a bill or a debt. Previously issued bills are never recalculated.</p>
        <div class="dashboard-table-wrap"><table class="dashboard-table">
            <thead><tr><th>Vendor</th><th>Stall &amp; contract</th><th>Agreed rent</th><th>Billing period</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                @php($rental = $row['rental'])
                <tr>
                    <td><strong>{{ $rental->vendor->name }}</strong><small>{{ $rental->vendor->email }}</small><small>{{ $rental->vendor->contact_number }}</small></td>
                    <td><strong>{{ $rental->stall->stall_number }} · {{ $rental->stall->market_section }}</strong><small>{{ $rental->stall->location }} · {{ $rental->stall->dimensions }}</small><small>{{ $rental->contract_number }}</small></td>
                    <td>₱{{ number_format((float) $rental->rent_amount, 2) }}<small>{{ $rental->billing_cycle }}</small></td>
                    <td>@if ($row['period']){{ $row['period']['start']->format('M d, Y') }} – {{ $row['period']['end']->format('M d, Y') }}
                        <small>@if ($row['bill'])Due {{ $row['bill']->due_date->format('M d, Y') }}
                            @elseif ($rental->billing_due_days !== null)Due {{ $row['period']['start']->addDays($rental->billing_due_days)->format('M d, Y') }}
                            @else Set payment deadline in review @endif</small>
                        @else — @endif</td>
                    <td>
                        @if ($row['bill'])<span class="table-status upcoming">{{ $row['bill']->sent_at ? 'Sent' : 'Existing bill' }} · {{ $row['bill']->status }}</span>
                        @elseif ($row['error']){{ $row['error'] }}
                        @elseif ($row['period']['partial'])<span class="table-status pending">Short period: manual review</span>
                        @else<span class="table-status upcoming">Ready to review</span>@endif
                    </td>
                    <td>@if ($row['bill'])<a href="{{ route('bills.show', $row['bill']) }}">View bill / notify</a>
                            @if ($row['nextBillingDate'])<a class="app-btn-secondary mt-2 inline-flex" href="{{ route('rental-billing.preview', ['rental' => $rental, 'billing_date' => $row['nextBillingDate']]) }}">Create next billing</a>@endif
                        @elseif (!$row['error'])<a href="{{ route('rental-billing.preview', ['rental' => $rental, 'billing_date' => $billingDate]) }}">Review &amp; send</a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No active rental contracts cover this date. Create or update a rental contract first; a vendor profile alone does not define a stall’s bill.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-4">{{ $rentals->links() }}</div>
    </section>
</x-layouts.admin>
