<x-layouts.app title="Record payment" active="payments">
    <div class="form-back"><a href="{{ route('payments') }}">← Back to Payments</a></div>
    <div class="page-heading vendor-form-heading"><div><h1>Record Payment</h1><p>Record a collection and issue a receipt for a vendor.</p></div></div>
    @if ($errors->any()) <div class="form-alert portal-alert">Please check the payment details and try again.</div> @endif
    <section class="form-panel payment-form-panel"><form method="POST" action="{{ route('payments.store') }}" class="portal-form">@csrf
        <div class="field"><label for="vendor_name">Vendor</label><select id="vendor_name" name="vendor_name" required><option value="">Select vendor</option>@foreach ($vendors as $vendor)<option value="{{ $vendor->name }}">{{ $vendor->name }}</option>@endforeach</select></div>
        <div class="field"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="0" step="0.01" placeholder="0.00" required></div>
        <div class="field"><label for="paid_at">Payment date</label><input id="paid_at" name="paid_at" type="date" value="{{ now()->format('Y-m-d') }}" required></div>
        <div class="field"><label for="receipt_number">Receipt number</label><input id="receipt_number" name="receipt_number" placeholder="OR-0062" required></div>
        <div class="form-actions"><a href="{{ route('payments') }}">Cancel</a><button class="black-button" type="submit">Save Payment <span>→</span></button></div>
    </form></section>
</x-layouts.app>
