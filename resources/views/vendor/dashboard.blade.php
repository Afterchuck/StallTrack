<x-layouts.vendor title="Vendor Dashboard">
    @php
        $vendorPayments = $payments ?? collect();
        $firstName = explode(' ', auth()->user()->name)[0];
        $totalPaid = $vendorPayments->sum('amount');
    @endphp

    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <p class="m-0 text-[10px] font-semibold tracking-[.12em] text-emerald-700">
            {{ $vendor?->stall_number ?: 'STALL PENDING ASSIGNMENT' }} · MUNICIPAL MARKET
        </p>

        <section class="mb-8 mt-2 flex items-end justify-between gap-5">
            <div>
                <h1 class="m-0 text-[28px] font-bold tracking-[-.03em] text-slate-900">Good day, {{ $firstName }}</h1>
                <p class="mb-0 mt-2 text-sm text-slate-500">Here is a quick view of your stall lease and payment records.</p>
            </div>
            <a class="hidden shrink-0 text-xs font-semibold text-emerald-700 no-underline hover:underline sm:block" href="{{ route('vendor.stall') }}">View lease details →</a>
        </section>

        @if (session('success'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif

        <section class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Account summary">
            <article class="border border-slate-200 bg-white px-5 py-4"><span class="block text-[10px] font-semibold uppercase tracking-[.1em] text-slate-500">Total paid to date</span><strong class="mt-2 block text-xl font-bold tracking-tight text-slate-900">₱{{ number_format((float) $totalPaid, 2) }}</strong><small class="mt-2 block text-[10px] text-slate-500">Recorded by Market Administration</small></article>
            <article class="border border-slate-200 bg-white px-5 py-4"><span class="block text-[10px] font-semibold uppercase tracking-[.1em] text-slate-500">Monthly stall rent</span><strong class="mt-2 block text-xl font-bold tracking-tight text-slate-900">{{ $vendor?->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</strong><small class="mt-2 block text-[10px] text-slate-500">{{ $vendor?->billing_cycle ?: 'Billing cycle not set' }}</small></article>
            <article class="border border-slate-200 bg-white px-5 py-4"><span class="block text-[10px] font-semibold uppercase tracking-[.1em] text-slate-500">Lease status</span><strong class="mt-2 block text-xl font-bold tracking-tight text-emerald-700">{{ $vendor?->status ?: 'Pending' }}</strong><small class="mt-2 block text-[10px] text-slate-500">{{ $vendor?->contract_end_date?->format('M d, Y') ?: 'Contract dates not set' }}</small></article>
            <article class="border border-slate-200 bg-white px-5 py-4"><span class="block text-[10px] font-semibold uppercase tracking-[.1em] text-slate-500">Payments on file</span><strong class="mt-2 block text-xl font-bold tracking-tight text-slate-900">{{ $vendorPayments->count() }}</strong><small class="mt-2 block text-[10px] text-slate-500">Admin-recorded receipts</small></article>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
            <div class="grid content-start gap-5">
                <section class="flex items-center gap-4 border border-emerald-200 bg-emerald-50 px-5 py-4">
                    <div class="grid size-7 shrink-0 place-items-center rounded-full bg-emerald-700 text-sm font-bold text-white">✓</div>
                    <div><h2 class="m-0 text-base font-bold tracking-tight text-slate-900">Payment records are managed by the Market Administration</h2><p class="mb-0 mt-1.5 text-xs leading-5 text-slate-600">New payments and receipts will appear here after the admin records them.</p></div>
                    <a class="ml-auto shrink-0 text-xs font-semibold text-emerald-700 no-underline hover:underline" href="{{ route('vendor.payments') }}">View all →</a>
                </section>

                <section class="border border-slate-200 bg-white p-5" id="payments">
                    <div class="mb-5 flex items-start justify-between gap-3"><div><h2 class="m-0 text-base font-bold tracking-tight text-slate-900">Payment history</h2><p class="mb-0 mt-1.5 text-xs leading-5 text-slate-500">Official payments recorded by Market Administration.</p></div></div>
                    <div class="overflow-x-auto"><table class="w-full min-w-[520px] border-collapse text-left"><thead><tr>@foreach (['Receipt', 'Date', 'Amount', 'Status'] as $heading)<th class="border-y border-slate-200 bg-slate-50 px-3 py-3 text-[10px] font-semibold uppercase tracking-[.08em] text-slate-500">{{ $heading }}</th>@endforeach</tr></thead><tbody>
                        @forelse ($vendorPayments->take(4) as $payment)
                            <tr><td class="border-b border-slate-100 px-3 py-3 text-xs text-slate-600">{{ $payment->receipt_number }}</td><td class="border-b border-slate-100 px-3 py-3 text-xs text-slate-600">{{ $payment->paid_at->format('M d, Y') }}</td><td class="border-b border-slate-100 px-3 py-3 text-xs font-semibold text-slate-900">₱{{ number_format((float) $payment->amount, 2) }}</td><td class="border-b border-slate-100 px-3 py-3 text-xs"><span class="inline-flex rounded-sm px-2 py-1 text-[9px] font-semibold {{ $payment->status === 'Paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $payment->status }}</span></td></tr>
                        @empty
                            <tr><td class="px-3 py-8 text-center text-xs text-slate-500" colspan="4">No payments have been recorded yet.</td></tr>
                        @endforelse
                    </tbody></table></div>
                </section>
            </div>

            <aside class="grid content-start gap-5">
                <section class="border border-slate-200 bg-white p-5" id="lease"><span class="text-[10px] font-semibold uppercase tracking-[.1em] text-emerald-700">My stall</span><h2 class="mt-2 text-base font-bold tracking-tight text-slate-900">{{ $vendor?->stall_number ?: 'Pending assignment' }}</h2><p class="mb-0 mt-1.5 text-xs leading-5 text-slate-500">{{ $vendor?->market_section ?: 'Market section not assigned' }}</p><dl class="my-5 grid gap-3 border-y border-slate-200 py-4"><div class="flex items-center justify-between gap-3"><dt class="text-[10px] text-slate-500">Lease period</dt><dd class="m-0 text-[11px] font-semibold text-slate-700">{{ $vendor?->contract_start_date?->format('M Y') ?: 'Not set' }} – {{ $vendor?->contract_end_date?->format('M Y') ?: 'Not set' }}</dd></div><div class="flex items-center justify-between gap-3"><dt class="text-[10px] text-slate-500">Monthly rent</dt><dd class="m-0 text-[11px] font-semibold text-slate-700">{{ $vendor?->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</dd></div><div class="flex items-center justify-between gap-3"><dt class="text-[10px] text-slate-500">Stall status</dt><dd class="m-0"><span class="inline-flex rounded-sm bg-emerald-100 px-2 py-1 text-[9px] font-semibold text-emerald-700">{{ $vendor?->status ?: 'Pending' }}</span></dd></div></dl><a class="text-xs font-semibold text-emerald-700 no-underline hover:underline" href="{{ route('vendor.stall') }}">View full lease →</a></section>
                <section class="border border-slate-200 bg-white p-5"><span class="text-[10px] font-semibold uppercase tracking-[.1em] text-emerald-700">Notice</span><h2 class="mt-2 text-base font-bold tracking-tight text-slate-900">Keep your permit visible</h2><p class="mb-0 mt-1.5 text-xs leading-5 text-slate-500">Display your current Mayor’s Permit on the front of your stall at all times.</p></section>
            </aside>
        </div>
    </div>
</x-layouts.vendor>
