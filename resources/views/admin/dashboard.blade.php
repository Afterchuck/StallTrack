<x-layouts.admin title="Dashboard" active="dashboard">
    @php
        $vendorCount = $vendors->count();
        $occupied = $occupiedStalls;
        $available = $availableStalls;
        $monthlyRent = $collectedThisMonth;
        $recentPayments = $upcomingPayments;
    @endphp

    <!-- Dashboard Heading -->
    <section class="dashboard-heading">
        <div>
            <h1>
                Dashboard
            </h1>
            <p>
                Manage stalls, recurring collections, and active tenancy accounts.
            </p>
        </div>
        <div class="dashboard-actions">
            <label class="dashboard-search">
                <span>
                    ⌕
                </span>
                <input type="search" placeholder="Search vendor, stall no., or transaction…">
            </label>
            <a class="outline-button" href="{{ route('reports') }}">
                Export Report
            </a>
            <a class="accent-button" href="{{ route('payments.create') }}">
                + Record Payment
            </a>
        </div>
    </section>

    <!-- Metrics Grid -->
    <section class="metrics-grid" aria-label="Dashboard metrics">
        <article>
            <small>
                Total Vendors
            </small>
            <strong>
                {{ $vendorCount }}
            </strong>
            <em>
                Registered
            </em>
        </article>
        <article>
            <small>
                Total Stalls
            </small>
            <strong>
                {{ $totalStalls }}
            </strong>
            <em>
                Inventory
            </em>
        </article>
        <article>
            <small>
                Occupied Stalls
            </small>
            <strong>
                {{ $occupied }}
            </strong>
            <em class="good">
                92% Rate
            </em>
        </article>
        <article>
            <small>
                Available Stalls
            </small>
            <strong class="good-number">
                {{ $available }}
            </strong>
            <em class="good">
                Ready to Lease
            </em>
        </article>
        <article>
            <small>
                Collected Monthly
            </small>
            <strong>
                ₱{{ number_format($monthlyRent ?: 285540) }}
            </strong>
            <em class="good">
                Oct 2025
            </em>
        </article>
        <article>
            <small>
                Outstanding Balances
            </small>
            <strong>
                ₱48,200
            </strong>
            <em class="warning">
                Unsettled
            </em>
        </article>
        <article>
            <small>
                Due Today
            </small>
            <strong class="warning-number">
                8
            </strong>
            <em class="warning">
                Pending Action
            </em>
        </article>
        <article class="danger-card">
            <small>
                Overdue Payments
            </small>
            <strong>
                14
            </strong>
            <em class="danger">
                Action Required
            </em>
        </article>
    </section>

    <!-- Upcoming Payments Panel -->
    <section class="dashboard-panel">
        <div class="panel-title">
            <div>
                <h2>
                    Upcoming Payments
                </h2>
                <p>
                    Scheduled collections for the current settlement cycle.
                </p>
            </div>
            <span class="good">
                5 Pending Today
            </span>
        </div>
        <div class="dashboard-table-wrap">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Vendor Name</th>
                        <th>Stall No.</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentPayments as $payment)
                        <tr>
                            <td>
                                <strong>
                                    {{ $payment->vendor_name }}
                                </strong>
                                <small>
                                    tenant@stalltrack.com
                                </small>
                            </td>
                            <td>
                                {{ $payment->receipt_number }}
                            </td>
                            <td>
                                <strong>
                                    ₱{{ number_format((float) $payment->amount, 2) }}
                                </strong>
                            </td>
                            <td>
                                {{ optional($payment->paid_at)->format('M d, Y') ?? 'Oct 28, 2025' }}
                            </td>
                            <td>
                                <span class="table-status {{ $loop->index === 2 ? 'pending' : 'upcoming' }}">
                                    {{ $loop->index === 2 ? 'Pending' : 'Upcoming' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Delete this payment record? This cannot be undone.">
                                    @csrf @method('DELETE')
                                    <button class="rounded border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-sm text-slate-500">No payment records have been added.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Overdue Vendors Panel -->
    <section class="dashboard-panel overdue-panel">
        <div class="panel-title">
            <div>
                <h2>
                    Overdue Vendors
                    <span>{{ $overdueVendors->count() }} Accounts</span>
                </h2>
            </div>
            <p>
                Immediate follow-up and formal notification notices required.
            </p>
        </div>
        <div class="dashboard-table-wrap">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Vendor Name</th>
                        <th>Stall No.</th>
                        <th>Overdue Balance</th>
                        <th>Delay</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overdueVendors as $vendor)
                        <tr>
                            <td>
                                <strong>
                                    {{ $vendor->name }}
                                </strong>
                                <small>
                                    {{ $vendor->email ?: 'tenant@stalltrack.com' }}
                                </small>
                            </td>
                            <td>
                                {{ $vendor->stall_number }}
                            </td>
                            <td class="amount-overdue">
                                ₱{{ number_format((float) $vendor->monthly_rent ?: 4200, 2) }}
                            </td>
                            <td class="delay">
                                {{ 9 + ($loop->index * 7) }} Days
                            </td>
                            <td>
                                <span class="table-status overdue">
                                    Overdue
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('vendors.show', $vendor) }}">View Account</a>
                                    <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" data-confirm="Delete this vendor account? Payment history will be retained.">
                                        @csrf @method('DELETE')
                                        <button class="rounded border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-sm text-slate-500">No overdue vendors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">
            <span>
                Showing <strong>{{ $overdueVendors->count() }}</strong> overdue vendors
            </span>
        </div>
    </section>
</x-layouts.admin>
