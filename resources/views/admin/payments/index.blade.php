<x-layouts.admin title="Collections" active="payments">
    <section class="page-heading"><h1>Bills &amp; collections</h1><p>Send contract-based rent bills, then record full or partial payments received by the office.</p></section>
    <section class="dashboard-panel mb-5">
        <div class="panel-title"><div><h2>Bill your rented stalls</h2><p>See vendors, stalls, contract rates, and billing periods. Review before sending a bill and notifying the vendor.</p></div><a class="app-btn-primary" href="{{ route('rental-billing', ['vendor_id' => request('vendor_id')]) }}">Rental billing · Review &amp; send</a></div>
    </section>
    @if (session('success'))<div class="success-alert" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="form-alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="GET" action="{{ route('payments') }}" class="dashboard-panel mb-5 flex flex-wrap items-end gap-3">
        <x-collection-query :except="['q', 'vendor_id']" />
        <label class="field grow">Search bills &amp; receipts<input type="search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Vendor, email, stall, contract, receipt"></label>
        <label class="field">Vendor<select name="vendor_id"><option value="">All vendors</option>@foreach ($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></label>
        <button class="app-btn-primary" type="submit">Search</button><a href="{{ route('payments') }}">Reset all</a>
    </form>
    <x-collection-chart :chart="$chart" />
    <section class="vendor-summary-grid">
        <article><small>Confirmed collections · all dates, matching search/vendor/method</small><strong>₱{{ number_format((float) $paidTotal, 2) }}</strong></article>
        <article><small>Outstanding · matching bill filters</small><strong>₱{{ number_format((float) $balanceTotal, 2) }}</strong></article>
    </section>
    <section class="dashboard-panel">
        <div class="panel-title"><div><h2>Billing periods</h2><p>{{ $bills->total() }} matching bills. Due-date and status filters apply here only; the chart uses payment dates.</p></div></div>
        <form method="GET" action="{{ route('payments') }}" class="mb-5 flex flex-wrap items-end gap-3">
            <x-collection-query :except="['status', 'due_from', 'due_to', 'sort']" />
            <label class="field">Status<select name="status">
                <option value="" @selected(request()->has('status') && !request('status'))>All bills</option>
                @foreach (['Outstanding', 'Unpaid', 'Partially paid', 'Paid', 'Overdue'] as $status)
                    <option value="{{ $status }}" @selected(request('status', 'Outstanding') === $status)>{{ $status }}</option>
                @endforeach
            </select></label>
            <label class="field">Due from<input type="date" name="due_from" value="{{ request('due_from') }}"></label>
            <label class="field">Due to<input type="date" name="due_to" value="{{ request('due_to') }}"></label>
            <label class="field">Sort<select name="sort">@foreach (['due_asc' => 'Due date: earliest', 'due_desc' => 'Due date: latest', 'newest' => 'Newest bill', 'amount_desc' => 'Highest amount'] as $key => $label)<option value="{{ $key }}" @selected(request('sort', 'due_asc') === $key)>{{ $label }}</option>@endforeach</select></label>
            <button class="app-btn-primary" type="submit">Filter</button>
        </form>
        <x-bill-table :bills="$bills" :manage="true" />
        <div class="mt-4">{{ $bills->links() }}</div>
    </section>

    <details class="form-panel" id="new-bill" @if ($errors->any() || request('rental_id')) open @endif>
        <summary class="cursor-pointer text-lg font-bold">Manual exception bill</summary>
        <p class="mt-3 text-sm text-slate-500">For an agreed shortened period or legacy rental only. Use Rental billing for regular rent. Manual bills do not notify vendors until you click Notify vendor on the bill. Existing overlapping bills are blocked.</p>
        <form method="POST" action="{{ route('bills.store') }}" class="portal-form">
            @csrf
            <label class="field">Vendor<select name="vendor_id" id="bill-vendor" required>
                <option value="">Select vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" data-rate="{{ $vendor->monthly_rent }}" @selected(old('vendor_id', request('vendor_id')) == $vendor->id)>{{ $vendor->name }} — {{ $vendor->stall_number ?: 'Unassigned' }} ({{ $vendor->billing_cycle }})</option>
                @endforeach
            </select></label>
            <div class="form-grid">
                <label class="field">Period starts<input type="date" name="period_start" value="{{ old('period_start', request('period_start', today()->startOfMonth()->toDateString())) }}" required></label>
                <label class="field">Period ends<input type="date" name="period_end" value="{{ old('period_end', request('period_end', today()->endOfMonth()->toDateString())) }}" required></label>
            </div>
            <label class="field">Rental / stall (select for contract exceptions)<select name="rental_id">
                <option value="">Legacy bill — no linked rental</option>
                @foreach ($rentals as $rental)
                    <option value="{{ $rental->id }}" @selected(old('rental_id', request('rental_id')) == $rental->id)>{{ $rental->vendor->name }} · {{ $rental->stall->stall_number }} · {{ $rental->contract_number }}</option>
                @endforeach
            </select></label>
            <div class="form-grid">
                <label class="field">Amount due (₱)<input id="bill-amount" type="number" name="amount" min="0.01" max="99999999.99" step="0.01" value="{{ old('amount') }}" required></label>
                <label class="field">Due date<input type="date" name="due_date" value="{{ old('due_date') }}" required></label>
            </div>
            <button type="submit" class="app-btn-primary">Create bill</button>
        </form>
    </details>
    <section class="dashboard-panel mt-5">
        <div class="panel-title"><div><h2>Payment receipts</h2><p>{{ $receipts->total() }} matching receipts in the chart’s payment-date range. Receipt status and assignment filters below do not change the confirmed-collections chart. For unassigned receipts, open the matching bill and choose “Apply existing receipt”.</p></div></div>
        <form method="GET" action="{{ route('payments') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <x-collection-query :except="['receipt_status', 'assignment']" />
            <label class="field">Receipt status<select name="receipt_status"><option value="">All statuses</option>@foreach (['Paid' => 'Confirmed', 'Recorded' => 'Unconfirmed', 'Reversed' => 'Reversed'] as $value => $label)<option value="{{ $value }}" @selected(request('receipt_status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="field">Assignment<select name="assignment"><option value="">All receipts</option><option value="linked" @selected(request('assignment') === 'linked')>Linked to bill</option><option value="unassigned" @selected(request('assignment') === 'unassigned')>Unassigned</option></select></label>
            <button class="app-btn-primary" type="submit">Filter receipts</button>
        </form>
        <div class="dashboard-table-wrap"><table class="dashboard-table">
            <thead><tr><th>Vendor</th><th>Receipt</th><th>Amount</th><th>Date</th><th>Method</th><th>Status</th><th>Bill</th></tr></thead>
            <tbody>@forelse ($receipts as $payment)
                <tr><td>{{ $payment->vendor_name }}</td><td>{{ $payment->receipt_number }}</td><td>₱{{ number_format((float) $payment->amount, 2) }}</td><td>{{ $payment->paid_at->format('M d, Y') }}</td><td>{{ $payment->payment_method ?: 'Unspecified' }}</td><td>{{ $payment->status === 'Paid' ? 'Confirmed' : $payment->status }}</td><td>@if ($payment->bill)<a href="{{ route('bills.show', $payment->bill) }}">#{{ $payment->bill_id }} · {{ $payment->bill->stall_number }}</a>@else Unassigned @endif</td></tr>
            @empty<tr><td colspan="7">No receipts match these filters.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $receipts->links() }}
    </section>
</x-layouts.admin>
