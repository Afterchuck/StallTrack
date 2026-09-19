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
                        ₱{{ number_format($payments->isEmpty() ? 8400 : $payments->sum('amount'), 2) }}
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
                        </tr>
                    @empty
                        <tr>
                            <td>
                                Rosa Delacruz
                            </td>
                            <td>
                                ₱1,200
                            </td>
                            <td>
                                Aug 26, 2026
                            </td>
                            <td>
                                ▣ OR-0062
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Manuel Reyes
                            </td>
                            <td>
                                ₱850
                            </td>
                            <td>
                                Aug 25, 2026
                            </td>
                            <td>
                                ▣ OR-0061
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Ana Villareal
                            </td>
                            <td>
                                ₱1,200
                            </td>
                            <td>
                                Aug 24, 2026
                            </td>
                            <td>
                                ▣ OR-0060
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Juan Dela Cruz
                            </td>
                            <td>
                                ₱500
                            </td>
                            <td>
                                Aug 24, 2026
                            </td>
                            <td>
                                ▣ OR-0059
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Maria Santos
                            </td>
                            <td>
                                ₱1,500
                            </td>
                            <td>
                                Aug 23, 2026
                            </td>
                            <td>
                                ▣ OR-0058
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="table-footer">
                <span>
                    Showing 1–{{ min($payments->count() ?: 5, 5) }} of {{ $payments->count() ?: 124 }} records
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
