<x-layouts.admin title="Bill payment" active="payments">
    <a class="font-semibold text-emerald-700" href="{{ route('payments', ['vendor_id' => $bill->vendor_id]) }}">Back to collections</a>
    <section class="page-heading"><h1>{{ $bill->vendor_name }} — Bill #{{ $bill->id }}</h1><p>Stall {{ $bill->stall_number ?: 'unassigned' }} · {{ $bill->period_start->format('M d, Y') }} – {{ $bill->period_end->format('M d, Y') }}</p></section>
    @if (session('success'))<div class="success-alert" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="form-alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="dashboard-panel mb-5">
        <h2 class="text-lg font-bold">Vendor notification</h2>
        @if ($bill->contract_number)<p class="text-sm text-slate-500">Contract {{ $bill->contract_number }}</p>@endif
        <p class="text-sm text-slate-500">{{ $bill->sent_at ? 'Sent to vendor portal '.$bill->sent_at->format('M d, Y H:i').' UTC' : 'No notification sent yet.' }}
            @if ($bill->last_reminded_at) · Last reminder {{ $bill->last_reminded_at->format('M d, Y H:i') }} UTC @endif
        </p>
        @if ($bill->status !== 'Paid')
            <form method="POST" action="{{ route('bills.notify', $bill) }}" class="mt-3 flex flex-wrap items-center gap-3">
                @csrf
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required>Notify the vendor about the remaining balance only.</label>
                <button class="app-btn-primary" type="submit">{{ $bill->sent_at ? 'Send reminder' : 'Notify vendor' }}</button>
            </form>
            <p class="mt-2 text-xs text-slate-500">One notification per bill every 24 hours. No new bill or payment is created.</p>
        @else<p class="text-sm text-emerald-700">Fully paid — no payment reminder needed.</p>@endif
    </section>
    <section class="vendor-summary-grid">
        <article><small>Bill amount</small><strong>₱{{ number_format((float) $bill->amount, 2) }}</strong></article>
        <article><small>Paid so far</small><strong>₱{{ number_format((float) $bill->paid_amount, 2) }}</strong></article>
        <article><small>Remaining</small><strong>₱{{ number_format((float) $bill->balance, 2) }}</strong></article>
        <article><small>Due {{ $bill->due_date->format('M d, Y') }}</small><strong>{{ $bill->status }}</strong>@if ($bill->is_overdue)<small class="text-rose-700">Overdue</small>@endif</article>
    </section>

    @if ($bill->status !== 'Paid')
        <button type="button" class="app-btn-primary mt-5" onclick="document.getElementById('payment-dialog').showModal()">Record full or partial payment</button>
        <dialog id="payment-dialog" class="m-auto w-full max-w-lg rounded-xl border border-slate-200 p-6 shadow-xl backdrop:bg-slate-900/50" aria-labelledby="payment-title">
            <h2 id="payment-title" class="m-0 text-xl font-bold">Confirm manual payment</h2>
            @if ($errors->any())<div class="form-alert" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <p class="text-sm text-slate-600">{{ $bill->vendor_name }} · Bill #{{ $bill->id }} · Balance ₱{{ number_format((float) $bill->balance, 2) }}</p>
            <form method="POST" action="{{ route('bills.payments.store', $bill) }}" class="portal-form" onsubmit="this.querySelector('button[type=submit]').disabled = true">
                @csrf
                <label class="field">Amount received (₱)<input id="received-amount" type="number" name="amount" min="0.01" max="{{ $bill->balance }}" step="0.01" value="{{ old('amount', $bill->balance) }}" required></label>
                <p id="remaining-preview" class="text-sm text-emerald-700" aria-live="polite"></p>
                <label class="field">Payment date<input type="date" name="paid_at" max="{{ today()->toDateString() }}" value="{{ old('paid_at', today()->toDateString()) }}" required></label>
                <p class="text-sm text-slate-500">A unique receipt number will be generated automatically when this payment is confirmed.</p>
                <label class="field">Payment method<select name="payment_method" required>@foreach (['Cash', 'Bank transfer', 'Other'] as $method)<option value="{{ $method }}" @selected(old('payment_method', 'Cash') === $method)>{{ $method }}</option>@endforeach</select></label>
                <label class="field">Notes (optional)<textarea name="notes" maxlength="1000" rows="2" class="rounded border border-slate-300 p-2">{{ old('notes') }}</textarea></label>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required>I confirm this money has been received and the payment details are correct.</label>
                <div class="form-actions">
                    <button type="button" class="app-btn-cancel" onclick="document.getElementById('payment-dialog').close()">Cancel</button>
                    <button type="submit" class="app-btn-primary">Confirm payment</button>
                </div>
            </form>
        </dialog>
        @if ($legacyPayments->isNotEmpty())
            <details class="form-panel mt-5">
                <summary class="cursor-pointer font-semibold">Apply existing receipt</summary>
                <form method="POST" action="{{ route('bills.receipts.allocate', $bill) }}" class="portal-form mt-3">
                    @csrf
                    <p class="text-sm text-slate-500">Use this for money already recorded for this vendor. The full receipt amount is applied to this bill without creating another receipt.</p>
                    <label class="field">Existing receipt<select name="payment_id" required><option value="">Choose receipt</option>@foreach ($legacyPayments as $receipt)<option value="{{ $receipt->id }}">{{ $receipt->receipt_number }} — ₱{{ number_format((float) $receipt->amount, 2) }} — {{ $receipt->paid_at->format('M d, Y') }} ({{ $receipt->status }})</option>@endforeach</select></label>
                    <label class="flex gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required>I confirm this receipt represents money received for this billing period.</label>
                    <button type="submit" class="app-btn-primary">Apply receipt to bill</button>
                </form>
            </details>
        @endif
    @else
        <div class="success-alert mt-5">This bill is fully paid. See the payment history below.</div>
    @endif

    <section class="dashboard-panel mt-5">
        <div class="panel-title"><div><h2>Payment history</h2><p>Each installment and reversal is retained. Reversing a record restores the bill's balance; it does not issue a refund.</p></div></div>
        <div class="dashboard-table-wrap"><table class="dashboard-table">
            <thead><tr><th>Receipt / date</th><th>Amount received</th><th>Method / staff</th><th>Status / notes</th><th>Correction</th></tr></thead>
            <tbody>@forelse ($bill->payments->sortByDesc('id') as $payment)
                <tr>
                    <td>{{ $payment->receipt_number }}<small>{{ $payment->paid_at->format('M d, Y') }}</small></td>
                    <td>₱{{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method ?: 'Not recorded' }}<small>{{ $payment->recorder?->name ?: 'Legacy record' }}</small></td>
                    <td>{{ $payment->status }}<small>{{ $payment->notes }}</small></td>
                    <td>
                        @if ($payment->status === 'Paid')
                            <details><summary class="cursor-pointer text-rose-700">Reverse payment</summary>
                                <form class="portal-form mt-2" method="POST" action="{{ route('bills.payments.reverse', [$bill, $payment]) }}" onsubmit="return confirm('Reverse this payment and restore the amount owed?')">
                                    @csrf @method('PATCH')
                                    <label class="field">Reason<textarea name="reversal_reason" maxlength="1000" rows="2" required></textarea></label>
                                    <button type="submit" class="app-btn-cancel">Confirm reversal</button>
                                </form>
                            </details>
                        @else
                            <span>{{ $payment->reversal_reason }}</span><small>{{ $payment->reverser?->name }} · {{ $payment->reversed_at?->format('M d, Y H:i') }}</small>
                        @endif
                    </td>
                </tr>
            @empty<tr><td colspan="5">No payments yet.</td></tr>@endforelse</tbody>
        </table></div>
    </section>
    @if ($bill->status !== 'Paid')
        <script>
            const received = document.getElementById('received-amount');
            const balanceCents = {{ \App\Models\Bill::cents($bill->balance) }};
            function previewBalance() {
                const amount = Number(received.value);
                const cents = Math.round(amount * 100);
                document.getElementById('remaining-preview').textContent =
                    !Number.isFinite(amount) || cents <= 0 || cents > balanceCents
                        ? 'Enter a positive amount no greater than the balance.'
                        : cents === balanceCents
                            ? 'This payment will settle the bill in full.'
                            : 'After this payment: ₱' + ((balanceCents - cents) / 100).toFixed(2) + ' remaining (Partially paid).';
            }
            received.addEventListener('input', previewBalance);
            previewBalance();
            @if ($errors->any() && old('amount') !== null)
                document.getElementById('payment-dialog').showModal();
            @endif
        </script>
    @endif
</x-layouts.admin>
