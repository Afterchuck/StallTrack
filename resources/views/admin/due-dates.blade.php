<x-layouts.admin title="Due Dates" active="due-dates">
    <section class="page-heading"><h1>Due Dates</h1><p>Keep rental renewals and payment follow-ups on schedule.</p></section>
    @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
    <form method="GET" action="{{ route('due-dates') }}" class="dashboard-panel mb-5 flex flex-wrap items-end gap-3">
        <label class="field">Search<input type="search" name="search" maxlength="100" value="{{ request('search') }}" placeholder="Vendor, stall, contract"></label>
        <label class="field">Contracts expiring within<select name="days">@foreach ([30, 60, 90] as $days)<option value="{{ $days }}" @selected(request('days', 90) == $days)>{{ $days }} days</option>@endforeach</select></label>
        <label class="field">Bills due<select name="due_status"><option value="">Today and overdue</option><option value="today" @selected(request('due_status') === 'today')>Today only</option><option value="overdue" @selected(request('due_status') === 'overdue')>Overdue only</option></select></label>
        <button type="submit" class="app-btn-primary">Filter</button><a href="{{ route('due-dates') }}">Reset</a>
    </form>
    <section class="dashboard-panel">
        <div class="panel-title"><h2>Contracts expiring within {{ request('days') ?: 90 }} days</h2></div>
        <div class="dashboard-table-wrap"><table class="dashboard-table">
            <thead><tr><th>Contract</th><th>Vendor</th><th>Stall</th><th>End date</th></tr></thead>
            <tbody>@forelse ($expiringRentals as $rental)
                <tr><td>{{ $rental->contract_number }}</td><td>{{ $rental->vendor->name }}</td><td>{{ $rental->stall->stall_number }}</td><td>{{ $rental->end_date->format('M d, Y') }}</td></tr>
            @empty<tr><td colspan="4">No expiring contracts match these filters.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $expiringRentals->links() }}
    </section>
    <section class="dashboard-panel">
        <div class="panel-title"><h2>Unpaid balances due today or overdue</h2></div>
        <x-bill-table :bills="$overdueBills" :manage="true" />
        {{ $overdueBills->links() }}
    </section>
</x-layouts.admin>
