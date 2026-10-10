@props(['bills', 'manage' => false])
<div class="dashboard-table-wrap">
    <table class="dashboard-table">
        <thead><tr>
            @if ($manage)<th>Vendor / stall</th>@endif
            <th>Billing period</th><th>Bill amount</th><th>Paid so far</th><th>Remaining</th><th>Due date</th><th>Status</th>
            @if ($manage)<th>Action</th>@else<th>Receipts</th>@endif
        </tr></thead>
        <tbody>
            @forelse ($bills as $bill)
                <tr>
                    @if ($manage)<td><strong>{{ $bill->vendor_name }}</strong><small>{{ $bill->stall_number ?: 'Unassigned' }}</small></td>@endif
                    <td>
                        @if (!$manage)<a href="{{ route('vendor.bills.show', $bill) }}">Stall {{ $bill->stall_number ?: 'Unassigned' }} · Bill #{{ $bill->id }}</a><br>@endif
                        {{ $bill->period_start->format('M d, Y') }} – {{ $bill->period_end->format('M d, Y') }}
                    </td>
                    <td>₱{{ number_format((float) $bill->amount, 2) }}</td>
                    <td>₱{{ number_format((float) $bill->paid_amount, 2) }}</td>
                    <td><strong>₱{{ number_format((float) $bill->balance, 2) }}</strong></td>
                    <td>{{ $bill->due_date->format('M d, Y') }}</td>
                    <td>
                        <span class="table-status {{ $bill->status === 'Paid' ? 'upcoming' : 'pending' }}">{{ $bill->status }}</span>
                        @if ($bill->is_overdue)<small class="text-rose-700">Overdue</small>@endif
                    </td>
                    @if ($manage)
                        <td><a href="{{ route('bills.show', $bill) }}">{{ $bill->status === 'Paid' ? 'Payment history' : 'Record payment' }}</a></td>
                    @else
                        <td class="space-y-2">
                            @forelse ($bill->payments->where('status', 'Paid') as $payment)
                                <x-receipt-download :payment="$payment" :bill="$bill" />
                            @empty
                                <span class="text-xs text-slate-500">Available after payment is confirmed</span>
                            @endforelse
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $manage ? 8 : 7 }}">No bills to show.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
