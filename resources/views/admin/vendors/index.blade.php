<x-layouts.admin title="Vendor Management" active="vendors">
    <section class="page-heading">
        <h1>Vendor Management</h1>
        <p>Review registrations, approve vendor accounts, and manage official rental records.</p>
    </section>

    @if (session('success'))
        <div class="success-alert">{{ session('success') }}</div>
    @endif

    <section class="vendor-summary-grid">
        <article><small>Registered vendors</small><strong>{{ $vendors->total() }}</strong></article>
        <article><small>Active vendors</small><strong>{{ $vendors->where('status', 'Active')->count() }}</strong></article>
        <article><small>Pending applications</small><strong>{{ $vendors->where('status', 'Pending')->count() }}</strong></article>
        <article><small>Monthly rent volume</small><strong>₱{{ number_format($vendors->sum('monthly_rent'), 2) }}</strong></article>
    </section>

    <div class="vendor-management-actions">
        <a class="vendor-primary-action" href="{{ route('vendors.create') }}">Add vendor</a>
        <a class="vendor-secondary-action" href="{{ route('stalls') }}">Manage stalls</a>
    </div>

    <form class="vendor-filter-bar mt-5" method="GET">
        <label class="vendor-search-control">
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Search vendor, stall, or section">
        </label>
        <select name="contract_status">
            <option value="">All statuses</option>
            <option value="Active" @selected(request('contract_status') === 'Active')>Active</option>
            <option value="Pending" @selected(request('contract_status') === 'Pending')>Pending</option>
        </select>
        <button class="vendor-secondary-action" type="submit">Filter</button>
    </form>

    <section class="vendor-directory-card">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Vendor</th><th>Contact</th><th>Stall</th><th>Section</th><th>Rate</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div>
                                        <strong>{{ $vendor->name }}</strong>
                                        <small>{{ $vendor->business_name ?: 'No business name recorded' }}</small>
                                    </div>
                                    <a
                                        class="inline-flex shrink-0 items-center rounded border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 no-underline transition hover:border-emerald-700 hover:bg-emerald-50 hover:text-emerald-700"
                                        href="{{ route('vendors.edit', $vendor) }}"
                                    >
                                        Edit details
                                    </a>
                                </div>
                            </td>
                            <td>{{ $vendor->contact_number ?: 'Not provided' }}</td>
                            <td>{{ $vendor->stall_number ?: 'Pending assignment' }}</td>
                            <td>{{ $vendor->market_section ?: 'Not assigned' }}</td>
                            <td>{{ $vendor->monthly_rent ? '₱'.number_format((float) $vendor->monthly_rent, 2) : 'Not set' }}</td>
                            <td><span class="vendor-status-pill {{ $vendor->status === 'Active' ? 'active' : 'pending' }}">{{ $vendor->status }}</span></td>
                            <td>
                                <a class="vendor-primary-action" href="{{ route('vendors.show', $vendor) }}">Manage account</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No vendor records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="vendor-directory-footer">{{ $vendors->links() }}</div>
    </section>
</x-layouts.admin>
