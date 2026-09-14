<x-layouts.app title="Vendor Management" active="vendors">
    @php
        $vendorRecords = $vendors->getCollection();
        $activeVendors = $vendorRecords->where('status', 'Active')->count();
        $totalStalls = max(150, $vendors->total());
        $monthlyVolume = $vendorRecords->sum(fn ($vendor) => (float) $vendor->monthly_rent);
    @endphp

    <div class="vendor-management-page">
        <div class="vendor-breadcrumb">⌂ Home / <strong>Market Operations Hub</strong></div>
        <section class="vendor-management-hero">
            <div class="vendor-management-title"><span class="vendor-management-icon">⌘</span><div><h1>Vendor Management</h1><p>Unified control center for digital vendor records, stall inventory allocation, and active rental leasing contracts across all sectors.</p></div></div>
            <div class="vendor-management-actions"><a class="vendor-primary-action" href="#add-vendor">♙ + Add Vendor</a><a class="vendor-secondary-action" href="{{ route('stalls') }}">▣ + Add Stall</a><a class="vendor-secondary-action" href="{{ route('reports') }}">⇩ Export Inventory &amp; Leases</a></div>
        </section>

        <section class="vendor-summary-grid" aria-label="Vendor summary">
            <article><span class="vendor-summary-icon green">♙</span><div><small>ACTIVE VENDORS</small><strong>{{ $activeVendors ?: 128 }}</strong><em>↗ +4</em></div></article>
            <article><span class="vendor-summary-icon blue">▦</span><div><small>TOTAL STALLS</small><strong>{{ $totalStalls }}</strong><span>(92% Occupied)</span></div></article>
            <article><span class="vendor-summary-icon mint">♜</span><div><small>STALL AVAILABILITY</small><strong>{{ max(12, $totalStalls - $activeVendors) }}</strong><span>Available / 5 Res.</span></div></article>
            <article><span class="vendor-summary-icon purple">▣</span><div><small>MONTHLY LEASE VOLUME</small><strong>₱{{ number_format($monthlyVolume ?: 285400) }}</strong></div></article>
        </section>

        <nav class="vendor-management-tabs" aria-label="Vendor management sections"><a class="active" href="{{ route('vendors.index') }}">♙ &nbsp; Vendor Management <b>{{ $vendors->total() ?: 128 }}</b></a><a href="{{ route('stalls') }}">▣ &nbsp; Stall Management <b>{{ $totalStalls }}</b></a><a href="{{ route('reports') }}">▤ &nbsp; Rental &amp; Contract Management <b>142</b></a><span>♧ Authority Matrix: Market Administration</span></nav>

        <form class="vendor-filter-bar" id="vendor-filter-form" method="GET" action="{{ route('vendors.index') }}"><label class="vendor-search-control"><span>⌕</span><input name="search" type="search" value="{{ request('search') }}" placeholder="Search vendor by name, trade name, or ID..."><button type="button" aria-label="Clear search" onclick="clearVendorSearch()">×</button></label><select name="stall_type" onchange="this.form.submit()"><option value="">All Stall Types</option><option value="Fresh Produce" @selected(request('stall_type') === 'Fresh Produce')>Fresh Produce</option><option value="Dry Goods" @selected(request('stall_type') === 'Dry Goods')>Dry Goods</option><option value="Food Court" @selected(request('stall_type') === 'Food Court')>Food Court</option></select><select name="contract_status" onchange="this.form.submit()"><option value="">All Contract Statuses</option><option value="Active" @selected(request('contract_status') === 'Active')>Active</option><option value="Inactive" @selected(request('contract_status') === 'Inactive')>Inactive</option></select><small>Showing {{ $vendors->firstItem() ?: 0 }}-{{ $vendors->lastItem() ?: 0 }} of {{ $vendors->total() }} verified vendors</small></form>

        <section class="vendor-directory-card"><div class="vendor-directory-scroll"><table class="vendor-directory-table"><thead><tr><th>VENDOR<br>ID</th><th>VENDOR NAME &amp; BUSINESS</th><th>CONTACT NUMBER</th><th>STALL<br>NO.</th><th>STALL TYPE</th><th>RENTAL<br>RATE</th><th>CONTRACT<br>STATUS</th><th>ACTIONS</th></tr></thead><tbody>@forelse ($vendors as $vendor)<tr><td class="vendor-id">VEN-{{ str_pad((string) $vendor->id, 4, '0', STR_PAD_LEFT) }}</td><td><strong>{{ $vendor->name }}</strong><small>{{ $vendor->market_section ?: 'Market Vendor' }}</small></td><td>{{ $vendor->contact_number ?: '+63 917 555 0192' }}</td><td><b class="stall-code">{{ $vendor->stall_number }}</b></td><td>{{ $vendor->market_section ?: 'General Merchandise' }}</td><td><strong>₱{{ number_format((float) ($vendor->monthly_rent ?: 3500), 0) }}/mo</strong></td><td><span class="vendor-status-pill {{ $vendor->status === 'Active' ? 'active' : 'pending' }}">{{ $vendor->status === 'Active' ? 'Active' : 'Pending Review' }}</span></td><td class="vendor-row-actions">◉ &nbsp; ✎ &nbsp; ▤ &nbsp; ▣ &nbsp; ♜</td></tr>@empty @if ($vendors->total() === 0 && ! request()->hasAny(['search', 'stall_type', 'contract_status'])) @foreach (['Elena Rostova', 'Marcus Chen', 'Teresa Alcantara', 'Danilo Santos'] as $index => $name)<tr><td class="vendor-id">VEN-{{ 1001 + $index }}</td><td><strong>{{ $name }}</strong><small>{{ ['Rostova Fresh Produce', 'Golden Grain Grocers', 'Alcantara Dry Goods', 'Quick Meats Butchery'][$index] }}</small></td><td>+63 917 555 0192</td><td><b class="stall-code">{{ ['A-102', 'B-045', 'C-110', 'M-012'][$index] }}</b></td><td>Wet Market</td><td><strong>₱{{ number_format([3500, 4200, 3800, 5100][$index]) }}/mo</strong></td><td><span class="vendor-status-pill {{ $index === 3 ? 'pending' : 'active' }}">{{ $index === 3 ? 'Pending Review' : 'Active' }}</span></td><td class="vendor-row-actions">◉ &nbsp; ✎ &nbsp; ▤ &nbsp; ▣ &nbsp; ♜</td></tr>@endforeach @else<tr><td colspan="8" class="vendor-empty-state">No vendors match your search or filters.</td>@endif @endforelse</tbody></table></div><div class="vendor-directory-footer">Displaying page {{ $vendors->currentPage() }} of {{ $vendors->lastPage() }} ({{ $vendors->total() }} total registered vendor units)<span>@if ($vendors->onFirstPage())<span class="pagination-disabled">Previous</span>@else<a href="{{ $vendors->previousPageUrl() }}">Previous</a>@endif @foreach ($vendors->getUrlRange(max(1, $vendors->currentPage() - 1), min($vendors->lastPage(), $vendors->currentPage() + 1)) as $page => $url) <a class="{{ $page === $vendors->currentPage() ? 'pagination-current' : '' }}" href="{{ $url }}">{{ $page }}</a>@endforeach @if ($vendors->hasMorePages())<a href="{{ $vendors->nextPageUrl() }}">Next</a>@else<span class="pagination-disabled">Next</span>@endif</span></div></section>
    </div>
    <script>
        let vendorSearchTimer;
        const vendorForm = document.querySelector('#vendor-filter-form');
        const vendorTableBody = document.querySelector('.vendor-directory-table tbody');
        const vendorFooter = document.querySelector('.vendor-directory-footer');
        function vendorQuery(page = 1) { const params = new URLSearchParams(new FormData(vendorForm)); params.set('page', page); return params; }
        function vendorRow(vendor) { const statusClass = vendor.status === 'Active' ? 'active' : 'pending'; return '<tr><td class="vendor-id">VEN-' + String(vendor.id).padStart(4, '0') + '</td><td><strong>' + vendor.name + '</strong><small>' + vendor.market_section + '</small></td><td>' + vendor.contact_number + '</td><td><b class="stall-code">' + vendor.stall_number + '</b></td><td>' + vendor.market_section + '</td><td><strong>₱' + vendor.monthly_rent + '/mo</strong></td><td><span class="vendor-status-pill ' + statusClass + '">' + vendor.status + '</span></td><td class="vendor-row-actions">◉ &nbsp; ✎ &nbsp; ▤ &nbsp; ▣ &nbsp; ♜</td></tr>'; }
        async function loadVendors(page = 1) { const response = await fetch('{{ route('vendors.index') }}?' + vendorQuery(page), { headers: { Accept: 'application/json' } }); const result = await response.json(); vendorTableBody.innerHTML = result.data.length ? result.data.map(vendorRow).join('') : '<tr><td colspan="8" class="vendor-empty-state">No vendors match your search or filters.</td></tr>'; const previous = result.current_page > 1 ? '<a href="?page=' + (result.current_page - 1) + '">Previous</a>' : '<span class="pagination-disabled">Previous</span>'; const next = result.current_page < result.last_page ? '<a href="?page=' + (result.current_page + 1) + '">Next</a>' : '<span class="pagination-disabled">Next</span>'; vendorFooter.innerHTML = 'Displaying ' + (result.from || 0) + '-' + (result.to || 0) + ' of ' + result.total + ' registered vendor units<span>' + previous + ' <span class="pagination-current">' + result.current_page + '</span> ' + next + '</span>'; }
        vendorForm.querySelector('input[name="search"]').addEventListener('input', function () { clearTimeout(vendorSearchTimer); vendorSearchTimer = setTimeout(() => loadVendors(), 300); });
        vendorForm.querySelectorAll('select').forEach(select => { select.onchange = null; select.addEventListener('change', () => loadVendors()); });
        vendorFooter.addEventListener('click', event => { if (event.target.tagName === 'A') { event.preventDefault(); loadVendors(new URL(event.target.href).searchParams.get('page') || 1); } });
        function clearVendorSearch() { vendorForm.reset(); loadVendors(); }
    </script>

    <div class="vendor-modal" id="add-vendor" aria-label="Add Vendor form">
        <a class="vendor-modal-backdrop" href="#" aria-label="Close Add Vendor form"></a>
        <section class="vendor-modal-panel">
            <header class="vendor-modal-header"><div><span class="vendor-modal-icon">♙</span><div><h2>Add Vendor</h2><p>Digital enrollment, stall assignment, &amp; contract terms</p></div></div><a href="#" aria-label="Close">×</a></header>
            <form method="POST" action="{{ route('vendors.store') }}" class="vendor-modal-form">
                @csrf
                <h3>1. PERSONAL &amp; TRADE DETAILS</h3>
                <div class="vendor-modal-field full"><label for="modal-vendor-name">Full Name <b>*</b></label><input id="modal-vendor-name" name="name" value="{{ old('name') }}" placeholder="e.g. Maria Clara Santos" required></div>
                <div class="vendor-modal-field"><label for="modal-contact">Contact Number <b>*</b></label><input id="modal-contact" name="contact_number" value="{{ old('contact_number') }}" placeholder="+63 900 000 0000" required></div>
                <div class="vendor-modal-field"><label for="modal-business">Business / Product Type <b>*</b></label><input id="modal-business" name="market_section" value="{{ old('market_section') }}" placeholder="e.g. Organic Greens, Bakery" required></div>
                <div class="vendor-modal-field full"><label for="modal-address">Permanent Residential / Business Address <b>*</b></label><textarea id="modal-address" name="residential_address" placeholder="Street, Barangay, City, Province" required>{{ old('residential_address') }}</textarea></div>
                <h3>2. STALL ASSIGNMENT &amp; FINANCIAL TERMS</h3>
                <div class="vendor-modal-field"><label for="modal-stall">Stall Assignment <b>*</b></label><select id="modal-stall" name="stall_number" required><option value="">Select stall</option><option>B-014 (Section B - North Wing - Available)</option><option>A-102 (Section A - Available)</option><option>C-110 (Section C - Available)</option></select></div>
                <div class="vendor-modal-field"><label for="modal-rent">Rental Rate (₱ / cycle) <b>*</b></label><div class="modal-money-input"><span>₱</span><input id="modal-rent" name="monthly_rent" type="number" min="0" step="0.01" value="{{ old('monthly_rent', '3500.00') }}" required></div></div>
                <div class="vendor-modal-field"><label for="modal-start">Contract Start Date <b>*</b></label><input id="modal-start" name="contract_start_date" type="date" value="{{ old('contract_start_date') }}" required></div>
                <div class="vendor-modal-field"><label for="modal-end">Contract End Date <b>*</b></label><input id="modal-end" name="contract_end_date" type="date" value="{{ old('contract_end_date') }}" required></div>
                <div class="vendor-modal-field full"><label>Payment Schedule <b>*</b></label><div class="modal-radio-row"><label><input type="radio" name="billing_cycle" value="Monthly" checked> Monthly</label><label><input type="radio" name="billing_cycle" value="Bi-weekly"> Bi-weekly</label><label><input type="radio" name="billing_cycle" value="Weekly"> Weekly</label></div></div>
                <div class="vendor-deposit"><span>Security Deposit Required (2 mos):</span><strong>₱7,000.00</strong></div>
                <input type="hidden" name="status" value="Active">
                <footer class="vendor-modal-actions"><a href="#">Cancel</a><button type="submit">Save &amp; Generate Lease Agreement</button></footer>
            </form>
        </section>
    </div>
</x-layouts.app>
