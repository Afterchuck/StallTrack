<x-layouts.vendor title="Billing & Payment History" active="payments">
    @php
        $totalPaid = $payments->sum('amount');
        $latestPayment = $payments->first();
        $nextCycle = $vendor?->contract_until;
    @endphp

    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <p class="m-0 text-[10px] font-semibold tracking-[.12em] text-[#9aa4b5]">{{ $vendor?->stall_number ?: 'PENDING ASSIGNMENT' }} · MUNICIPAL MARKET</p>
        <section class="mt-2 flex flex-wrap items-start justify-between gap-4"><div><h1 class="m-0 text-3xl font-bold tracking-[-.03em] text-[#182033]">Billing &amp; Payment History</h1><p class="mt-2 max-w-3xl text-sm text-[#778397]">Official ledger of market treasury payments and verified rental receipts.</p></div><a class="text-sm font-semibold text-slate-700 no-underline hover:underline" href="{{ route('vendor.stall') }}">View lease details →</a></section>

        <section class="mt-9 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Total paid to date</small><strong class="mt-3 block text-2xl text-slate-900">₱{{ number_format((float) $totalPaid, 2) }}</strong><p class="mt-2 text-sm text-slate-500">{{ $payments->total() }} recorded receipt(s)</p></article>
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Latest receipt</small><strong class="mt-3 block text-2xl text-slate-900">{{ $latestPayment?->receipt_number ?: '—' }}</strong><p class="mt-2 text-sm text-slate-500">{{ $latestPayment?->paid_at?->format('M d, Y') ?: 'No payment recorded' }}</p></article>
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Rental rate</small><strong class="mt-3 block text-2xl text-slate-900">{{ $vendor?->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</strong><p class="mt-2 text-sm text-slate-500">{{ $vendor?->billing_cycle ?: 'Billing cycle not set' }}</p></article>
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Contract until</small><strong class="mt-3 block text-2xl text-slate-900">{{ $nextCycle?->format('M d, Y') ?: 'Not set' }}</strong><p class="mt-2 text-sm text-slate-500">Official contract term</p></article>
        </section>

        <section class="mt-9 rounded-md border border-slate-200 bg-white">
            <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-6 py-5"><div><h2 class="m-0 text-lg font-bold text-slate-800">Payment records &amp; digital receipts</h2><p class="mt-1 text-sm text-slate-500">Payments validated by the Municipal Market Treasury.</p></div><a class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 no-underline hover:bg-slate-50" href="#payment-form">Record payment</a></header>
            <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-4">Receipt</th><th class="px-6 py-4">Date received</th><th class="px-6 py-4">Amount paid</th><th class="px-6 py-4">Status</th></tr></thead><tbody>
                @forelse ($payments as $payment)
                    <tr class="border-t border-slate-100"><td class="px-6 py-4 font-mono font-semibold text-slate-700">{{ $payment->receipt_number }}</td><td class="px-6 py-4 text-slate-600">{{ $payment->paid_at->format('M d, Y') }}</td><td class="px-6 py-4 font-semibold text-slate-900">₱{{ number_format((float) $payment->amount, 2) }}</td><td class="px-6 py-4"><span class="rounded px-2 py-1 text-xs font-semibold {{ $payment->status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $payment->status }}</span></td></tr>
                @empty
                    <tr><td class="px-6 py-8 text-center text-slate-500" colspan="4">No payments have been recorded for your account.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="border-t border-slate-200 px-6 py-4">{{ $payments->links() }}</div>
        </section>

        <section class="mt-6 rounded-md border border-slate-200 bg-white p-6" id="payment-form"><h2 class="m-0 text-lg font-bold text-slate-800">Record a payment</h2><p class="mt-1 text-sm text-slate-500">Enter the details from the official receipt issued at the Market Treasury.</p><form class="mt-5 grid gap-4 md:grid-cols-2" method="POST" action="{{ route('vendor.payments.store') }}">@csrf<label class="grid gap-2 text-xs font-semibold text-slate-600">Amount paid<input class="rounded border border-slate-300 px-3 py-2" name="amount" type="number" min="0.01" step="0.01" required></label><label class="grid gap-2 text-xs font-semibold text-slate-600">Payment date<input class="rounded border border-slate-300 px-3 py-2" name="paid_at" type="date" value="{{ today()->toDateString() }}" required></label><label class="grid gap-2 text-xs font-semibold text-slate-600">Receipt number<input class="rounded border border-slate-300 px-3 py-2" name="receipt_number" required></label><button class="self-end rounded bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black" type="submit">Submit payment</button></form></section>
    </div>
</x-layouts.vendor>
