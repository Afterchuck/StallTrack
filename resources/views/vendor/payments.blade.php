<x-layouts.vendor title="Bills & Payment History" active="payments">
    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <section class="page-heading"><h1>Bills &amp; payment history</h1><p>Full and partial payments recorded by Market Administration.</p></section>
        <section class="mb-5 rounded-md border border-slate-200 bg-white p-5">
            <small class="text-slate-500">Total confirmed payments</small>
            <strong class="mt-2 block text-2xl">₱{{ number_format((float) $totalPaid, 2) }}</strong>
            <p class="text-sm text-slate-500">Reversed and unconfirmed receipts are excluded.</p>
        </section>
        <section class="mb-6 rounded-md border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">Your billing periods</h2>
            <x-bill-table :bills="$bills" />
            {{ $bills->withQueryString()->links() }}
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">Receipt history</h2>
            <div class="dashboard-table-wrap"><table class="dashboard-table">
                <thead><tr><th>Receipt</th><th>Billing period</th><th>Date received</th><th>Amount received</th><th>Status</th><th>Export</th></tr></thead>
                <tbody>@forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>{{ $payment->bill ? $payment->bill->period_start->format('M d, Y').' – '.$payment->bill->period_end->format('M d, Y') : 'Not assigned to a bill' }}</td>
                        <td>{{ $payment->paid_at->format('M d, Y') }}</td>
                        <td>₱{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->status === 'Paid' ? 'Confirmed' : $payment->status }}</td>
                        <td><x-receipt-download :payment="$payment" :bill="$payment->bill" /></td>
                    </tr>
                @empty<tr><td colspan="6">No payments have been recorded for your account.</td></tr>@endforelse</tbody>
            </table></div>
            <div class="mt-4">{{ $payments->withQueryString()->links() }}</div>
        </section>
    </div>
</x-layouts.vendor>
