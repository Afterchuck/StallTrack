<x-layouts.vendor title="Stall & Account Details" active="stall">
    <div class="mx-auto max-w-[1180px] px-5 py-8 md:px-8 md:py-10">
        <p class="m-0 text-[10px] font-semibold tracking-[.12em] text-[#9aa4b5]">
            {{ $vendor?->stall_number ?: 'PENDING ASSIGNMENT' }} · MUNICIPAL MARKET
        </p>

        <section class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="m-0 text-3xl font-bold tracking-[-.03em] text-[#182033]">Stall &amp; Account Details</h1>
                <p class="mt-2 max-w-3xl text-sm text-[#778397]">Official contract terms, stall assignment details, and verified vendor contact information on file with the market administrator.</p>
            </div>
            <span class="inline-flex items-center gap-2 text-xs text-[#7b8595]"><i class="size-2 rounded-full bg-slate-400"></i>Read-only system record</span>
        </section>

        <section class="mt-9 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Stall designation</small><strong class="mt-3 block text-2xl text-slate-900">{{ $vendor?->stall_number ?: 'Pending' }}</strong><p class="mt-2 text-sm text-slate-500">{{ $vendor?->market_section ?: 'Awaiting market assignment' }}</p></article>
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Assigned size</small><strong class="mt-3 block text-2xl text-slate-900">Not recorded</strong><p class="mt-2 text-sm text-slate-500">Contact Market Admin for details</p></article>
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Rate per period</small><strong class="mt-3 block text-2xl text-slate-900">{{ $vendor?->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</strong><p class="mt-2 text-sm text-slate-500">{{ $vendor?->billing_cycle ?: 'Billing cycle not set' }}</p></article>
            <article class="rounded-md border border-slate-200 bg-white p-6"><small class="font-semibold uppercase tracking-wide text-slate-400">Contract status</small><strong class="mt-3 block text-2xl text-emerald-600">{{ $vendor?->status ?: 'Pending' }}</strong><p class="mt-2 text-sm text-slate-500">{{ $vendor?->contract_until?->format('M Y') ?: 'Contract dates not set' }}</p></article>
        </section>

        <div class="mt-10 grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="rounded-md border border-slate-200 bg-white">
                <header class="border-b border-slate-200 px-7 py-6"><h2 class="m-0 text-lg font-bold text-slate-800">Stall &amp; Contract Specifications</h2><p class="mt-1 text-sm text-slate-500">Physical assignment and registered lease specifications.</p></header>
                <dl class="grid gap-x-8 md:grid-cols-2">
                    @foreach ([['Stall identification', $vendor?->stall_number ?: 'Pending assignment', $vendor?->market_section ?: 'Market section not assigned'], ['Monthly stall rental fee', $vendor?->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2).' / cycle' : 'Not set', $vendor?->billing_cycle ?: 'Billing cycle not set'], ['Official contract validity', $vendor?->contract_start_date?->format('M d, Y').' – '.$vendor?->contract_end_date?->format('M d, Y'), 'Contract dates on file'], ['Lease reference', 'Vendor record #'.str_pad((string) ($vendor?->id ?: 0), 4, '0', STR_PAD_LEFT), 'Registered with Market Administration']] as [$label, $value, $detail])
                        <div class="border-b border-slate-100 px-7 py-6"><dt class="text-sm text-slate-400">{{ $label }}</dt><dd class="mt-2 font-semibold text-slate-800">{{ $value }}</dd><small class="mt-1 block text-xs text-slate-500">{{ $detail }}</small></div>
                    @endforeach
                </dl>
            </section>

            <aside class="rounded-md border border-slate-200 bg-white p-6"><h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Contact information on file</h2><dl class="mt-5 grid gap-5 text-sm"><div><dt class="text-slate-400">Registered vendor</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor?->name ?: auth()->user()->name }}</dd></div><div><dt class="text-slate-400">Mobile number</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor?->contact_number ?: 'Not provided' }}</dd></div><div><dt class="text-slate-400">Email address</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor?->email ?: auth()->user()->email }}</dd></div><div><dt class="text-slate-400">Address</dt><dd class="mt-1 font-semibold text-slate-800">{{ $vendor?->residential_address ?: 'Not provided' }}</dd></div></dl></aside>
        </div>
    </div>
</x-layouts.vendor>
