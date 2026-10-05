<x-layouts.admin title="Stall Details" active="stalls">
    <div class="form-back"><a href="{{ route('stalls') }}">← Back to stalls</a></div>

    <section class="page-heading">
        <div>
            <h1>Stall {{ $stall->stall_number }}</h1>
            <p>Stall inventory and current assignment details.</p>
        </div>
        <span class="vendor-status-pill {{ $stall->status === 'Available' ? 'active' : 'pending' }}">{{ $stall->status }}</span>
    </section>

    <section class="dashboard-panel mt-5">
        <div class="panel-title">
            <div>
                <h2>Stall details</h2>
                <p>Current information for this market unit.</p>
            </div>
        </div>
        <dl class="grid gap-5 text-sm md:grid-cols-2">
            <div><dt class="text-slate-500">Stall number</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->stall_number }}</dd></div>
            <div><dt class="text-slate-500">Market section</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->market_section }}</dd></div>
            <div><dt class="text-slate-500">Location</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->location ?: 'Not set' }}</dd></div>
            <div><dt class="text-slate-500">Type</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->stall_type ?: 'Not specified' }}</dd></div>
            <div><dt class="text-slate-500">Dimensions</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->length_m !== null && $stall->width_m !== null ? $stall->length_m.'m × '.$stall->width_m.'m ('.number_format((float) $stall->length_m * (float) $stall->width_m, 2).' m²)' : ($stall->dimensions ?: 'Not set') }}</dd></div>
            <div><dt class="text-slate-500">Rate per square meter</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->rate_per_sqm !== null ? '₱'.number_format((float) $stall->rate_per_sqm, 2) : 'Not set' }}</dd></div>
            <div><dt class="text-slate-500">Monthly rate</dt><dd class="mt-1 font-semibold text-slate-800">{{ $stall->monthly_rate !== null ? '₱'.number_format((float) $stall->monthly_rate, 2) : 'Not set' }}</dd></div>
            <div><dt class="text-slate-500">Assigned vendor</dt><dd class="mt-1 font-semibold text-slate-800">{{ $rental?->vendor?->name ?: 'Vacant' }}</dd></div>
        </dl>
    </section>
</x-layouts.admin>
