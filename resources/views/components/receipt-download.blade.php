@props(['payment', 'bill' => null])

@if ($payment->status === 'Paid')
    <button
        type="button"
        class="app-btn-secondary min-h-0 px-3 py-1.5 text-xs"
        data-open-receipt
        data-receipt-number="{{ $payment->receipt_number }}"
        data-vendor-name="{{ $bill?->vendor_name ?? $payment->vendor_name }}"
        data-bill-number="{{ $bill ? 'Bill #'.$bill->id : 'Unassigned payment' }}"
        data-stall-number="{{ $bill?->stall_number ?: 'Unassigned' }}"
        data-billing-start="{{ $bill?->period_start?->format('M d, Y') ?: '—' }}"
        data-billing-end="{{ $bill?->period_end?->format('M d, Y') ?: '—' }}"
        data-amount="{{ number_format((float) $payment->amount, 2) }}"
        data-confirmed-date="{{ $payment->paid_at->format('M d, Y') }}"
        data-payment-method="{{ $payment->payment_method ?: 'Unspecified' }}"
    >View</button>
@else
    <span class="text-xs text-slate-500">{{ $payment->status === 'Recorded' ? 'Pending confirmation' : 'Receipt unavailable' }}</span>
@endif
