@props(['chart'])
<section class="vendor-directory-card mb-5 p-5">
    <div class="panel-title"><div><h2>Collected payments</h2><p>{{ $chart['from'] }} – {{ $chart['to'] }} · {{ $chart['count'] }} confirmed receipts · ₱{{ number_format((float) $chart['total'], 2) }}</p></div></div>
    <form method="GET" action="{{ route('payments') }}" class="mb-4 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <x-collection-query :except="['group', 'from', 'to', 'method']" />
        <label class="field">Group by<select name="group">@foreach (['daily', 'weekly', 'monthly', 'yearly'] as $group)<option value="{{ $group }}" @selected($chart['group'] === $group)>{{ ucfirst($group) }}</option>@endforeach</select></label>
        <label class="field">Payment date from<input type="date" name="from" value="{{ request('from') }}"></label>
        <label class="field">Payment date to<input type="date" name="to" value="{{ request('to') }}"></label>
        <label class="field">Payment method<select name="method"><option value="">All methods</option>@foreach (['Cash', 'Bank transfer', 'Other', 'Unspecified'] as $method)<option @selected(request('method') === $method)>{{ $method }}</option>@endforeach</select></label>
        <button class="app-btn-primary" type="submit">Update chart</button>
    </form>
    <p class="mb-4 text-sm text-slate-500">Includes full and partial payments by payment date, including unassigned confirmed receipts. Reversed and unconfirmed receipts are excluded. Weeks run Monday–Sunday; boundary periods include only dates in the selected range. Blank dates use the latest 30 days, 12 weeks, 12 months, or 5 years.</p>
    @if ($chart['count'] === 0)<p class="rounded-md bg-slate-50 p-3 text-sm">No confirmed payments match these filters.</p>@endif
    @php
        $width = max(720, count($chart['points']) * 64 + 100);
        $step = ($width - 100) / count($chart['points']);
        $maximum = max(1, $chart['maximum']);
    @endphp
    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Scrollable collected payment chart">
        <svg viewBox="0 0 {{ $width }} 320" width="{{ $width }}" height="320" class="block min-w-full" role="img" aria-labelledby="collections-chart-title collections-chart-description">
            <title id="collections-chart-title">{{ ucfirst($chart['group']) }} confirmed collections in Philippine pesos</title>
            <desc id="collections-chart-description">Collected ₱{{ number_format((float) $chart['total'], 2) }}. Exact values are available in the data table below.</desc>
            @foreach ([0, 0.5, 1] as $tick)
                <line x1="90" y1="{{ 240 - 200 * $tick }}" x2="{{ $width }}" y2="{{ 240 - 200 * $tick }}" stroke="#e2e8f0"/>
                <text x="82" y="{{ 244 - 200 * $tick }}" text-anchor="end" fill="#64748b" font-size="11">₱{{ number_format($chart['maximum'] * $tick / 100, 2) }}</text>
            @endforeach
            @foreach ($chart['points'] as $point)
                @php($height = $point['cents'] / $maximum * 200)
                <rect x="{{ 100 + $loop->index * $step }}" y="{{ 240 - $height }}" width="{{ max(8, $step - 18) }}" height="{{ $height }}" rx="3" fill="#087e69">
                    <title>{{ $point['label'] }}: ₱{{ number_format($point['cents'] / 100, 2) }}</title>
                </rect>
                <text transform="translate({{ 105 + $loop->index * $step }},258) rotate(30)" font-size="10" fill="#475569">{{ $point['label'] }}</text>
            @endforeach
        </svg>
    </div>
    <details class="mt-4"><summary class="cursor-pointer text-sm font-semibold text-emerald-700">View exact chart values</summary>
        <div class="dashboard-table-wrap mt-3"><table class="dashboard-table"><thead><tr><th>Period</th><th>Collected (₱)</th></tr></thead><tbody>
            @foreach ($chart['points'] as $point)<tr><td>{{ $point['label'] }}</td><td>{{ number_format($point['cents'] / 100, 2) }}</td></tr>@endforeach
        </tbody></table></div>
    </details>
</section>
