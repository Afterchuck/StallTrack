<x-layouts.admin title="Stall Management" active="stalls">
    <section class="page-heading">
        <h1>Stall Management</h1>
        <p>Manage market units, availability, and current vendor assignments.</p>
    </section>

    <section class="vendor-summary-grid">
        <article><small>Total stalls</small><strong>{{ $stalls->count() }}</strong></article>
        <article><small>Occupied</small><strong>{{ $stalls->where('status', 'Occupied')->count() }}</strong></article>
        <article><small>Available</small><strong>{{ $stalls->where('status', 'Available')->count() }}</strong></article>
        <article><small>Inactive</small><strong>{{ $stalls->where('status', 'Inactive')->count() }}</strong></article>
    </section>

    <section class="vendor-directory-card">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Stall</th><th>Section</th><th>Type</th><th>Vendor</th><th>Monthly rate</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($stalls as $stall)
                        @php($rental = $stall->rentals->firstWhere('status', 'Active'))
                        <tr>
                            <td><strong>{{ $stall->stall_number }}</strong><small>{{ $stall->location ?: 'Location not set' }}</small></td>
                            <td>{{ $stall->market_section }}</td>
                            <td>{{ $stall->stall_type ?: 'Not specified' }}<small>{{ $stall->dimensions ?: 'Dimensions not set' }}</small></td>
                            <td>{{ $rental?->vendor?->name ?: 'Vacant' }}</td>
                            <td>{{ $stall->monthly_rate !== null ? '₱'.number_format((float) $stall->monthly_rate, 2) : 'Not set' }}</td>
                            <td><span class="vendor-status-pill {{ $stall->status === 'Available' ? 'active' : 'pending' }}">{{ $stall->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No stalls have been registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
