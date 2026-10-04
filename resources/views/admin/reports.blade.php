<x-layouts.admin title="Reports" active="reports">
    <section class="mb-6 flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <p class="mb-2 text-[10px] font-bold uppercase tracking-[.16em] text-emerald-700">Market operations</p>
            <h1 class="m-0 text-2xl font-bold tracking-tight text-slate-900">Reports</h1>
            <p class="mt-2 mb-0 text-sm text-slate-500">A current overview of vendor accounts, stalls, leases, and collections.</p>
        </div>
        <a class="inline-flex items-center gap-2 rounded-md bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white no-underline hover:bg-emerald-900" href="{{ route('reports', ['export' => 1]) }}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Export CSV
        </a>
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Report summary">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <small class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vendors</small>
            <strong class="mt-2 block text-2xl font-bold text-slate-900">{{ number_format($vendorCount) }}</strong>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <small class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active rentals</small>
            <strong class="mt-2 block text-2xl font-bold text-slate-900">{{ number_format($activeRentalCount) }}</strong>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <small class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total collected</small>
            <strong class="mt-2 block text-2xl font-bold text-emerald-800">₱{{ number_format((float) $totalCollected, 2) }}</strong>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <small class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding</small>
            <strong class="mt-2 block text-2xl font-bold text-rose-700">₱{{ number_format((float) $outstandingBalance, 2) }}</strong>
        </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="m-0 text-base font-semibold text-slate-900">Stall availability</h2>
                <p class="mt-1 mb-0 text-xs text-slate-500">Current stall count by status</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($stallCounts as $status => $total)
                    <div class="flex items-center justify-between px-5 py-4">
                        <span class="text-sm text-slate-600">{{ $status }}</span>
                        <strong class="text-sm font-semibold text-slate-900">{{ number_format($total) }}</strong>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">No stall records yet.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="m-0 text-base font-semibold text-slate-900">Vendor and lease summary</h2>
                <p class="mt-1 mb-0 text-xs text-slate-500">Collected and outstanding amounts are based on each vendor’s payment records.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] border-collapse text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3 font-semibold">Vendor</th><th class="px-4 py-3 font-semibold">Stall</th><th class="px-4 py-3 font-semibold">Status</th><th class="px-4 py-3 text-right font-semibold">Collected</th><th class="px-4 py-3 text-right font-semibold">Outstanding</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($vendors as $vendor)
                            <tr>
                                <td class="px-4 py-3"><strong class="block font-semibold text-slate-800">{{ $vendor->name }}</strong><small class="text-xs text-slate-500">{{ $vendor->market_section ?: 'No section assigned' }}</small></td>
                                <td class="px-4 py-3 text-slate-600">{{ $vendor->stall_number ?: 'Unassigned' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $vendor->status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $vendor->status }}</span></td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700">₱{{ number_format((float) ($vendor->collected_total ?? 0), 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700">₱{{ number_format((float) ($vendor->outstanding_total ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No vendor records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="m-0 text-base font-semibold text-slate-900">Recent payment activity</h2>
            <p class="mt-1 mb-0 text-xs text-slate-500">The 12 most recently created payment records</p>
        </div>
        <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] border-collapse text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3 font-semibold">Vendor</th><th class="px-4 py-3 font-semibold">Receipt</th><th class="px-4 py-3 font-semibold">Date</th><th class="px-4 py-3 font-semibold">Status</th><th class="px-4 py-3 text-right font-semibold">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $payment->vendor?->name ?? $payment->vendor_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->receipt_number ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ ($payment->paid_at ?? $payment->due_date ?? $payment->created_at)?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $payment->status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $payment->status }}</span></td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">₱{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No payment records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
