<x-layouts.admin title="Dashboard" active="dashboard">
    <section class="dashboard-heading">
        <div><h1>Dashboard</h1><p>Manage stalls, collections, and outstanding vendor balances.</p></div>
        <div class="dashboard-actions"><a class="outline-button" href="{{ route('reports') }}">View reports</a><a class="accent-button" href="{{ route('payments') }}">Bills &amp; collections</a></div>
    </section>
    <section class="metrics-grid" aria-label="Dashboard metrics">
        <article><small>Total vendors</small><strong>{{ $vendors->count() }}</strong><em>Registered</em></article>
        <article><small>Total stalls</small><strong>{{ $totalStalls }}</strong><em>Inventory</em></article>
        <article><small>Occupied stalls</small><strong>{{ $occupiedStalls }}</strong><em>Occupied</em></article>
        <article><small>Available stalls</small><strong>{{ $availableStalls }}</strong><em>Ready to lease</em></article>
        <article><small>Collected this month</small><strong>₱{{ number_format((float) $collectedMonthly, 2) }}</strong><em>{{ today()->format('M Y') }} · confirmed receipts</em></article>
        <article><small>Outstanding balances</small><strong>₱{{ number_format((float) $outstandingTotal, 2) }}</strong><em>Unpaid bill balances</em></article>
        <article><small>Due today</small><strong>{{ $dueToday }}</strong><em>Bills with a balance</em></article>
        <article><small>Overdue bills</small><strong>{{ $overdueCount }}</strong><em>Follow-up needed</em></article>
    </section>
    <form method="GET" action="{{ route('dashboard') }}" class="dashboard-panel flex flex-wrap items-end gap-3">
        @if ($errors->any())<p class="w-full text-sm text-rose-700" role="alert">{{ $errors->first() }}</p>@endif
        <label class="field grow">Search outstanding bills<input type="search" name="search" maxlength="100" value="{{ request('search') }}" placeholder="Vendor, stall, or contract"></label>
        <button type="submit" class="app-btn-primary">Search</button><a href="{{ route('dashboard') }}">Reset</a>
        <p class="w-full text-sm text-slate-500">Search applies to the two bill lists below (up to 8 matches each). Summary metrics above remain market-wide.</p>
    </form>
    <section class="dashboard-panel">
        <div class="panel-title"><div><h2>Upcoming &amp; outstanding payments</h2><p>Full and partial payments are recorded against a specific bill.</p></div><a href="{{ route('payments') }}">{{ $dueToday }} due today · View all bills</a></div>
        <x-bill-table :bills="$outstandingBills" :manage="true" />
        @if ($outstandingBills->isEmpty())<p class="text-sm text-slate-500">{{ request('search') ? 'No outstanding bills match your search.' : 'No outstanding bills.' }} <a class="font-semibold text-emerald-700" href="{{ route('rental-billing') }}">Review &amp; send rental bills</a> when a billing period is ready.</p>@endif
    </section>
    <section class="dashboard-panel overdue-panel">
        <div class="panel-title"><div><h2>Overdue balances</h2><p>Only the unpaid portion is shown as outstanding. A partial payment does not clear an overdue balance.</p></div></div>
        <x-bill-table :bills="$overdueBills" :manage="true" />
    </section>
</x-layouts.admin>
