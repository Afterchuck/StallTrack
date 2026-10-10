<x-layouts.admin title="Reports" active="reports">
    <section class="page-heading">
        <h1>Reports</h1>
        <p>Review registration, occupancy, and payment activity.</p>
    </section>

    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('reports') }}" class="mb-5 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <label class="field">Section<select name="section"><option value="">All sections</option>@foreach ($sections as $section)<option @selected(request('section') === $section)>{{ $section }}</option>@endforeach</select></label>
        <label class="field">From<input type="date" name="from" value="{{ request('from') }}"></label>
        <label class="field">To<input type="date" name="to" value="{{ request('to') }}"></label>
        <label class="field">Search activity<input type="search" name="search" maxlength="100" value="{{ request('search') }}" placeholder="Description, action, staff"></label>
        <label class="field">Action<select name="action"><option value="">All actions</option>@foreach ($actions as $action)<option @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></label>
        <button type="submit" class="app-btn-primary">Filter reports</button><a class="text-sm font-semibold text-emerald-700" href="{{ route('reports') }}">Reset</a>
        <p class="w-full text-sm text-slate-500">Dates filter payment income and activity. Section filters current stall/vendor statistics and their balances/income using the vendor’s current section. Balances are current, not historical. Activity search/action filters affect the activity list only; activity remains across all sections.</p>
    </form>
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold">Outstanding bill balances</h2>
        <p class="text-xl font-semibold">₱{{ number_format((float) $outstandingBalance, 2) }}</p>
        <a class="text-sm font-semibold text-emerald-700" href="{{ route('payments') }}">Review unpaid and partially paid bills</a>
    </section>

    <section class="vendor-summary-grid mt-5">
        <article><small>Total vendors</small><strong>{{ $vendorCounts['total'] }}</strong></article>
        <article><small>Active vendors</small><strong>{{ $vendorCounts['active'] }}</strong></article>
        <article><small>Occupied stalls</small><strong>{{ $stallCounts['occupied'] }} / {{ $stallCounts['total'] }}</strong></article>
        <article><small>Paid income</small><strong>₱{{ number_format((float) $paidIncome, 2) }}</strong></article>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="vendor-directory-card">
            <header class="border-b border-slate-200 px-6 py-5">
                <h2 class="m-0 text-lg font-bold text-slate-900">Occupancy by market section</h2>
            </header>
            <div class="vendor-directory-scroll">
                <table class="vendor-directory-table">
                    <thead><tr><th>Section</th><th>Total</th><th>Occupied</th><th>Available</th></tr></thead>
                    <tbody>
                        @foreach ($sectionStats as $section)
                            <tr>
                                <td>{{ $section['section'] }}</td>
                                <td>{{ $section['total'] }}</td>
                                <td>{{ $section['occupied'] }}</td>
                                <td>{{ $section['available'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="vendor-directory-card">
            <header class="border-b border-slate-200 px-6 py-5">
                <h2 class="m-0 text-lg font-bold text-slate-900">Paid income by month</h2>
            </header>
            <div class="vendor-directory-scroll">
                <table class="vendor-directory-table">
                    <thead><tr><th>Month</th><th>Paid income</th></tr></thead>
                    <tbody>
                        @forelse ($monthlyIncome as $month => $amount)
                            <tr><td>{{ $month }}</td><td>₱{{ number_format($amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="2">No paid income has been recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="vendor-directory-card mt-5">
        <header class="border-b border-slate-200 px-6 py-5">
            <h2 class="m-0 text-lg font-bold text-slate-900">Recent activity</h2>
        </header>
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Action</th><th>Description</th><th>Staff</th><th>Time</th></tr></thead>
                <tbody>
                    @forelse ($recentActivity as $activity)
                        <tr>
                            <td><span class="vendor-status-pill active">{{ ucfirst($activity->action) }}</span></td>
                            <td>{{ $activity->description }}</td>
                            <td>{{ $activity->user?->name ?: 'System' }}</td>
                            <td>{{ $activity->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No administrative activity has been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $recentActivity->links() }}</div>
</x-layouts.admin>
