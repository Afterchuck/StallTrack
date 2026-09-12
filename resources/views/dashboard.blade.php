<x-layouts.app title="Vendors & stalls" active="vendors">
    <div class="page-heading"><div><h1>Vendors &amp; stalls</h1><p>registration and record-keeping (the "Record" objective)</p></div></div>
    <section class="vendor-section">
        <div class="section-heading"><div><h2>Vendors &amp; stalls</h2><p>{{ $vendors->count() }} registered vendors</p></div><a class="black-button" href="{{ route('vendors.create') }}">＋ Add vendor</a></div>
        @if (session('success')) <div class="success-alert">{{ session('success') }}</div> @endif
        <div class="vendor-tools"><label class="search-box"><span>⌕</span><input type="search" placeholder="Search vendor or stall number" oninput="filterVendors(this.value)"></label><select aria-label="Filter by status" onchange="filterStatus(this.value)"><option>All statuses</option><option>Active</option><option>Inactive</option></select></div>
        <div class="table-card"><table id="vendor-table"><thead><tr><th>Vendor</th><th>Stall no.</th><th>Contract</th><th>Status</th></tr></thead><tbody>
            @forelse ($vendors as $vendor)
                <tr data-status="{{ $vendor->status }}"><td>{{ $vendor->name }}</td><td>{{ $vendor->stall_number }}</td><td>{{ $vendor->contract_until ? 'Until '.$vendor->contract_until->format('M Y') : 'No contract date' }}</td><td><span class="status {{ strtolower($vendor->status) }}">{{ $vendor->status }}</span></td></tr>
            @empty
                <tr data-status="Active"><td>Rosa Delacruz</td><td>A-14</td><td>Until Dec 2026</td><td><span class="status active">Active</span></td></tr><tr data-status="Active"><td>Manuel Reyes</td><td>B-07</td><td>Until Mar 2027</td><td><span class="status active">Active</span></td></tr><tr data-status="Inactive"><td>Lina Bautista</td><td>C-22</td><td>Expired</td><td><span class="status inactive">Inactive</span></td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <script>
        function filterVendors(query) { const term = query.toLowerCase(); document.querySelectorAll('#vendor-table tbody tr').forEach(row => row.hidden = !row.innerText.toLowerCase().includes(term)); }
        function filterStatus(status) { document.querySelectorAll('#vendor-table tbody tr').forEach(row => row.hidden = status !== 'All statuses' && row.dataset.status !== status); }
    </script>
</x-layouts.app>
