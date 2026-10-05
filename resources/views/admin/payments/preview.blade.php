<x-layouts.admin title="Review rental bill" active="payments">
    <section class="page-heading"><h1>Review rental bill</h1><p>Nothing is created or sent until you confirm below.</p></section>
    <a href="{{ route('rental-billing', ['billing_date' => $billingDate]) }}">← Back to rental billing</a>
    @if ($errors->any())<div class="form-alert mt-4" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="form-panel mt-5">
        <h2 class="text-xl font-bold">{{ $rental->vendor->name }}</h2>
        <p>Stall {{ $rental->stall->stall_number }} · {{ $rental->stall->market_section }} · {{ $rental->stall->location }}</p>
        <p class="text-sm text-slate-500">Contract {{ $rental->contract_number }} · {{ $rental->start_date->format('M d, Y') }} – {{ $rental->end_date->format('M d, Y') }}</p>
        <dl class="my-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Billing period</dt><dd class="font-semibold">{{ $period['start']->format('M d, Y') }} – {{ $period['end']->format('M d, Y') }}</dd></div>
            <div><dt class="text-sm text-slate-500">Contract rent / {{ $rental->billing_cycle }} cycle</dt><dd class="text-2xl font-bold text-emerald-800">₱{{ number_format((float) $rental->rent_amount, 2) }}</dd></div>
            <div><dt class="text-sm text-slate-500">Earlier unpaid bills (all vendor stalls)</dt><dd>₱{{ number_format((float) $arrears, 2) }} — kept separate; not added again.</dd></div>
        </dl>
        @if ($existingBill)
            <div class="form-alert">An existing bill overlaps this period. <a href="{{ route('bills.show', $existingBill) }}">Open bill #{{ $existingBill->id }}</a> to notify the vendor or record a payment.</div>
        @elseif ($period['partial'])
            <div class="form-alert">The contract ends during this billing cycle. Agree the charge for this shortened period before creating a <a href="{{ route('payments', ['vendor_id' => $rental->vendor_id, 'rental_id' => $rental->id, 'period_start' => $period['start']->toDateString(), 'period_end' => $period['end']->toDateString()]) }}#new-bill">manual exception bill</a>. No proration or full-cycle charge is assumed.</div>
        @else
            <form action="{{ route('rental-billing.send', $rental) }}" method="POST" class="portal-form" id="send-rental-bill">
                @csrf
                <input type="hidden" name="billing_date" value="{{ $billingDate }}">
                <input type="hidden" name="review_token" value="{{ $reviewToken }}">
                <label class="field">Agreed payment deadline: days after period starts
                    <input type="number" id="due-days" name="due_days" min="0" max="365" step="1" value="{{ old('due_days', $rental->billing_due_days) }}" required>
                </label>
                <p class="text-sm text-slate-500">Use the deadline agreed with the vendor. 0 means the first day of the period, 9 means the tenth day. Saved for future bills on this contract; changing it does not alter existing bills.</p>
                <p class="rounded-md bg-emerald-50 p-3 font-semibold" role="status">Due date: <span id="due-date-preview">Choose the agreed deadline</span></p>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1">I confirm the stall, billing period, contract amount, and due date. Send this bill to the vendor portal.</label>
                <button type="submit" class="app-btn-primary" id="send-button">Send bill &amp; notify vendor</button>
                <p class="text-sm text-slate-500">In-app notification only. The vendor sees it after opening or refreshing their portal. This does not mark the bill as paid.</p>
            </form>
            <script>
                const dueDays = document.getElementById('due-days');
                const deadlineLabel = document.getElementById('due-date-preview');
                function updateDeadline() {
                    if (dueDays.value === '' || !dueDays.checkValidity()) {
                        deadlineLabel.textContent = 'Choose the agreed deadline';
                        return;
                    }
                    const date = new Date('{{ $period['start']->toDateString() }}T00:00:00Z');
                    date.setUTCDate(date.getUTCDate() + Number(dueDays.value));
                    deadlineLabel.textContent = date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' });
                }
                dueDays.addEventListener('input', updateDeadline);
                updateDeadline();
                document.getElementById('send-rental-bill').addEventListener('submit', () => {
                    document.getElementById('send-button').disabled = true;
                    document.getElementById('send-button').textContent = 'Sending…';
                });
            </script>
        @endif
    </section>
</x-layouts.admin>
