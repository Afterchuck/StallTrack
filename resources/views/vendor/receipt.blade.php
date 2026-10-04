<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $payment->receipt_number }} · StallTrack</title>
    <style>
        body { margin: 0; background: #f1f5f9; color: #111827; font-family: Arial, Helvetica, sans-serif; }
        .receipt { box-sizing: border-box; width: 80mm; margin: 24px auto; padding: 6mm; background: #fff; box-shadow: 0 8px 30px #0f172a1a; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .rule { border: 0; border-top: 1px dashed #94a3b8; margin: 14px 0; }
        .row { display: flex; justify-content: space-between; gap: 12px; margin: 9px 0; font-size: 12px; }
        .row strong { text-align: right; }
        .amount { font-size: 20px; font-weight: 700; }
        .actions { width: 80mm; margin: 0 auto 24px; text-align: center; }
        .actions a, .actions button { display: inline-block; margin: 4px; border: 0; border-radius: 5px; padding: 9px 13px; background: #087e69; color: white; font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .actions a { background: #e2e8f0; color: #334155; }
        @media print {
            @page { size: 80mm auto; margin: 3mm; }
            body { background: #fff; }
            .receipt { width: 100%; margin: 0; padding: 0; box-shadow: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <main class="receipt">
        <header class="center">
            <strong style="font-size: 18px;">StallTrack</strong>
            <p class="muted" style="margin: 5px 0 0; font-size: 11px;">Municipal Public Market</p>
            <h1 style="margin: 18px 0 4px; font-size: 14px; letter-spacing: .12em;">PAYMENT RECEIPT</h1>
            <p class="muted" style="margin: 0; font-size: 11px;">{{ $payment->receipt_number }}</p>
        </header>
        <hr class="rule">
        <div class="row"><span class="muted">Received from</span><strong>{{ $vendor->name }}</strong></div>
        <div class="row"><span class="muted">Stall</span><strong>{{ $vendor->stall_number ?: 'Not assigned' }}</strong></div>
        <div class="row"><span class="muted">Market section</span><strong>{{ $vendor->market_section ?: '—' }}</strong></div>
        <div class="row"><span class="muted">Date received</span><strong>{{ $payment->paid_at->format('M d, Y') }}</strong></div>
        <hr class="rule">
        <div class="row"><span class="muted">Amount paid</span><strong class="amount">₱{{ number_format((float) $payment->amount, 2) }}</strong></div>
        <div class="row"><span class="muted">Status</span><strong>PAID</strong></div>
        <hr class="rule">
        <p class="center muted" style="font-size: 11px; line-height: 1.5;">Thank you. Keep this receipt for your records.</p>
    </main>
    <div class="actions">
        <a href="{{ route('vendor.payments') }}">Back to payments</a>
        <button type="button" onclick="window.print()">Print receipt</button>
    </div>
</body>
</html>
