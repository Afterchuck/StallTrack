<x-layouts.admin title="Payments" active="payments">
    <div class="payment-page-heading">
        <h1>
            <span>
                2.
            </span> 
            Payments 
            <small>
                — recording collections and issuing receipts (the "Recall" objective)
            </small>
        </h1>
    </div>

    <section class="payments-section">
        <div class="payments-toolbar">
            <div>
                <h2>
                    Payments
                </h2>
                <p>
                    This month: 
                    <strong>
                        ₱{{ number_format($payments->filter(fn ($payment) => $payment->paid_at->isSameMonth(now()))->sum('amount'), 2) }}
                    </strong> 
                    collected
                </p>
            </div>
            <a class="black-button" href="{{ route('payments.create') }}">
                ＋ Record Payment
            </a>
        </div>

        @if (session('success'))
            <div class="success-alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ session('error') }}</div>
        @endif

        <div class="payment-filters">
            <label class="payment-search">
                <span>
                    ⌕
                </span>
                <input type="search" placeholder="Search vendor name" oninput="filterPayments(this.value)">
            </label>
            <input class="payment-date" type="date" aria-label="Filter by date" onchange="filterPaymentsByDate(this.value)">
        </div>

        <div class="table-card payment-table">
            <table id="payment-table">
                <thead>
                    <tr>
                        <th>
                            Vendor
                        </th>
                        <th>
                            Amount
                        </th>
                        <th>
                            Date
                        </th>
                        <th>
                            Receipt
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr data-date="{{ $payment->paid_at->format('Y-m-d') }}">
                            <td>
                                {{ $payment->vendor_name }}
                            </td>
                            <td>
                                ₱{{ number_format((float) $payment->amount, 2) }}
                            </td>
                            <td>
                                {{ $payment->paid_at->format('M d, Y') }}
                            </td>
                            <td>
                                ▣ {{ $payment->receipt_number }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Delete this payment record? This cannot be undone.">
                                    @csrf @method('DELETE')
                                    <button class="rounded border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-sm text-slate-500">No payment records have been added.</td></tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="table-footer">
                <span>
                    {{ $payments->count() }} payment records
                </span>
                <div>
                    <button disabled>
                        ‹
                    </button>
                    <button>
                        ›
                    </button>
                </div>
            </div>
        </div>
    </section>

    <script>
        function filterPayments(query) { 
            const term = query.toLowerCase(); 
            document.querySelectorAll('#payment-table tbody tr').forEach(row => row.hidden = !row.innerText.toLowerCase().includes(term)); 
        }

        function filterPaymentsByDate(date) { 
            document.querySelectorAll('#payment-table tbody tr').forEach(row => row.hidden = date && row.dataset.date !== date); 
        }
    </script>
</x-layouts.admin>
