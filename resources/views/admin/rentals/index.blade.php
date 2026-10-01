<x-layouts.admin title="Rental Management" active="rentals">
    <section class="page-heading">
        <h1>Rental Management</h1>
        <p>Track active leases, rental periods, and contract status.</p>
    </section>

    <section class="vendor-summary-grid">
        <article><small>Total contracts</small><strong>{{ $rentals->count() }}</strong></article>
        <article><small>Active contracts</small><strong>{{ $rentals->where('status', 'Active')->count() }}</strong></article>
        <article><small>Expiring soon</small><strong>{{ $rentals->filter(fn ($rental): bool => $rental->status === 'Active' && $rental->end_date->isBetween(today(), today()->addDays(30)))->count() }}</strong></article>
        <article><small>Expired</small><strong>{{ $rentals->where('status', 'Expired')->count() }}</strong></article>
    </section>

    <section class="vendor-directory-card">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Contract</th><th>Vendor</th><th>Stall</th><th>Term</th><th>Rent</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($rentals as $rental)
                        <tr>
                            <td><strong>{{ $rental->contract_number }}</strong></td>
                            <td>{{ $rental->vendor->name }}</td>
                            <td>{{ $rental->stall->stall_number }}</td>
                            <td>{{ $rental->start_date->format('M d, Y') }} – {{ $rental->end_date->format('M d, Y') }}</td>
                            <td>₱{{ number_format((float) $rental->rent_amount, 2) }} / {{ $rental->billing_cycle }}</td>
                            <td><span class="vendor-status-pill {{ $rental->status === 'Active' ? 'active' : 'pending' }}">{{ $rental->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No rental contracts have been registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
