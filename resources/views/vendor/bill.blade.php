<x-layouts.vendor title="Rental bill" active="payments">
    <section class="mx-auto flex max-w-6xl flex-col gap-5 p-5 md:p-8">
        <a href="{{ route('vendor.payments') }}" class="text-sm text-emerald-700">← All bills &amp; payments</a>
        <div><h1 class="text-2xl font-bold">Bill #{{ $bill->id }} · Stall {{ $bill->stall_number ?: 'Unassigned' }}</h1><p>{{ $bill->vendor_name }} @if ($bill->contract_number)· Contract {{ $bill->contract_number }}@endif</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><x-bill-table :bills="collect([$bill])" /></div>
        <p class="text-sm text-slate-600">Payments are received and recorded by market administration. Partial payments reduce this bill’s balance; contact the office if a receipt is missing or incorrect.</p>
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-bold">Payment history for this bill</h2>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead><tr><th class="p-2">Receipt</th><th class="p-2">Date</th><th class="p-2">Amount</th><th class="p-2">Status</th><th class="p-2">Export</th></tr></thead>
                <tbody>@forelse ($bill->payments as $payment)<tr class="border-t border-slate-100"><td class="p-2">{{ $payment->receipt_number }}</td><td class="p-2">{{ $payment->paid_at->format('M d, Y') }}</td><td class="p-2">₱{{ number_format((float) $payment->amount, 2) }}</td><td class="p-2">{{ $payment->status === 'Paid' ? 'Confirmed' : $payment->status }}</td><td class="p-2"><x-receipt-download :payment="$payment" :bill="$bill" /></td></tr>
                @empty<tr><td colspan="5" class="p-2">No payments recorded yet.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    </section>
</x-layouts.vendor>
