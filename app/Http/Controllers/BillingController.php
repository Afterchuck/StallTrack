<?php

namespace App\Http\Controllers;

use App\CollectionChart;
use App\Models\ActivityLog;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Vendor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, CollectionChart $charts): View
    {
        $request->validate([
            'status' => ['nullable', 'in:Outstanding,Unpaid,Partially paid,Paid,Overdue'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', 'in:daily,weekly,monthly,yearly'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'due_from' => ['nullable', 'date_format:Y-m-d'],
            'due_to' => ['nullable', 'date_format:Y-m-d'],
            'method' => ['nullable', 'in:Cash,Bank transfer,Other,Unspecified'],
            'receipt_status' => ['nullable', 'in:Paid,Recorded,Reversed'],
            'assignment' => ['nullable', 'in:linked,unassigned'],
            'sort' => ['nullable', 'in:due_asc,due_desc,newest,amount_desc'],
        ]);
        if ($request->filled('due_from') && $request->filled('due_to') && $request->input('due_from') > $request->input('due_to')) {
            throw ValidationException::withMessages(['due_to' => 'The due-date end must be on or after its start.']);
        }
        $query = Bill::query()->with('vendor');
        $payments = Payment::query();
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
            $payments->where('vendor_id', $request->integer('vendor_id'));
        }
        if ($request->filled('q')) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($request->input('q'))).'%';
            $query->where(fn ($bills) => $bills->whereRaw("vendor_name LIKE ? ESCAPE '!'", [$term])
                ->orWhereRaw("stall_number LIKE ? ESCAPE '!'", [$term])
                ->orWhereRaw("contract_number LIKE ? ESCAPE '!'", [$term])
                ->orWhereHas('vendor', fn ($vendors) => $vendors->whereRaw("name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("email LIKE ? ESCAPE '!'", [$term]))
                ->orWhereHas('payments', fn ($receipts) => $receipts->whereRaw("receipt_number LIKE ? ESCAPE '!'", [$term])));
            $payments->where(fn ($receipts) => $receipts->whereRaw("vendor_name LIKE ? ESCAPE '!'", [$term])
                ->orWhereRaw("receipt_number LIKE ? ESCAPE '!'", [$term])
                ->orWhereHas('vendor', fn ($vendors) => $vendors->whereRaw("name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("email LIKE ? ESCAPE '!'", [$term]))
                ->orWhereHas('bill', fn ($bills) => $bills->whereRaw("stall_number LIKE ? ESCAPE '!'", [$term])->orWhereRaw("contract_number LIKE ? ESCAPE '!'", [$term])));
        }
        if ($request->filled('method')) {
            $request->input('method') === 'Unspecified'
                ? $payments->whereNull('payment_method')
                : $payments->where('payment_method', $request->input('method'));
        }
        $chart = $charts->build($payments, $request->input('group') ?: 'daily', $request->input('from'), $request->input('to'));
        $receipts = (clone $payments)->whereDate('paid_at', '>=', $chart['from'])->whereDate('paid_at', '<=', $chart['to'])
            ->when($request->filled('receipt_status'), fn ($receipts) => $receipts->where('status', $request->input('receipt_status')))
            ->when($request->input('assignment') === 'linked', fn ($receipts) => $receipts->whereNotNull('bill_id'))
            ->when($request->input('assignment') === 'unassigned', fn ($receipts) => $receipts->whereNull('bill_id'));
        $query->when($request->filled('due_from'), fn ($bills) => $bills->whereDate('due_date', '>=', $request->input('due_from')))
            ->when($request->filled('due_to'), fn ($bills) => $bills->whereDate('due_date', '<=', $request->input('due_to')));
        $status = $request->input('status');
        $searchingBills = $request->filled('q') || $request->filled('vendor_id');
        $allBillsSelected = str_contains((string) $request->server('QUERY_STRING'), 'status=')
            && $request->input('status') === null;

        switch ($allBillsSelected ? null : ($status ?? ($searchingBills ? null : 'Outstanding'))) {
            case 'Outstanding':
                $query->outstanding();
                break;
            case 'Unpaid':
                $query->where('paid_amount', 0);
                break;
            case 'Partially paid':
                $query->outstanding()->where('paid_amount', '>', 0);
                break;
            case 'Paid':
                $query->whereColumn('paid_amount', 'amount');
                break;
            case 'Overdue':
                $query->outstanding()->whereDate('due_date', '<', today());
                break;
        }
        $summaryQuery = clone $query;
        [$column, $direction] = match ($request->input('sort') ?: 'due_asc') {
            'due_asc' => ['due_date', 'asc'],
            'due_desc' => ['due_date', 'desc'],
            'newest' => ['id', 'desc'],
            'amount_desc' => ['amount', 'desc'],
        };
        $query->orderBy($column, $direction)->orderBy('id');

        return view('admin.payments.index', [
            'bills' => $query->paginate(10)->withQueryString(),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name', 'stall_number', 'monthly_rent', 'billing_cycle']),
            'rentals' => Rental::with(['vendor', 'stall'])->orderBy('vendor_id')->get(),
            'balanceTotal' => (clone $summaryQuery)->sum('amount') - (clone $summaryQuery)->sum('paid_amount'),
            'paidTotal' => (clone $payments)->where('status', 'Paid')->whereNull('reversed_at')->sum('amount'),
            'chart' => $chart,
            'receipts' => $receipts->with('bill')->latest('paid_at')->latest('id')
                ->paginate(10, ['*'], 'receipts_page')->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'rental_id' => ['nullable', 'integer', 'exists:rentals,id'],
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:99999999.99'],
        ]);

        $bill = DB::transaction(function () use ($data, $request): Bill {
            $vendor = Vendor::lockForUpdate()->findOrFail($data['vendor_id']);
            $rental = ! empty($data['rental_id']) ? Rental::with('stall')->lockForUpdate()->findOrFail($data['rental_id']) : null;
            if ($rental && ($rental->vendor_id !== $vendor->id
                || $data['period_start'] < $rental->start_date->toDateString()
                || $data['period_end'] > $rental->end_date->toDateString())) {
                throw ValidationException::withMessages(['rental_id' => 'Choose this vendor’s rental and a period within its contract dates.']);
            }
            if ($vendor->bills()->when($rental, fn ($query) => $query->where(fn ($bills) => $bills->where('rental_id', $rental->id)->orWhereNull('rental_id')))
                ->where('period_start', '<=', $data['period_end'])
                ->where('period_end', '>=', $data['period_start'])->exists()) {
                throw ValidationException::withMessages(['period_start' => 'A bill already covers part or all of this period. Open that bill to record another payment.']);
            }
            $bill = Bill::create([
                ...$data, 'vendor_name' => $vendor->name, 'stall_number' => $rental?->stall->stall_number ?? $vendor->stall_number,
                'contract_number' => $rental?->contract_number,
                'paid_amount' => '0.00', 'created_by' => $request->user()->id,
            ]);
            $this->audit($request, $bill, 'bill_created', 'Created bill #'.$bill->id);

            return $bill;
        });

        return redirect()->route('bills.show', $bill)->with('success', 'Bill created. Record the amount actually received, or apply an existing receipt.');
    }

    public function show(Bill $bill): View
    {
        return view('admin.payments.bill', [
            'bill' => $bill->load(['vendor', 'payments.recorder', 'payments.reverser']),
            'legacyPayments' => Payment::where('vendor_id', $bill->vendor_id)->whereNull('bill_id')
                ->whereIn('status', ['Paid', 'Recorded'])->orderByDesc('paid_at')->get(),
        ]);
    }

    public function record(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:99999999.99'],
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'receipt_number' => ['required', 'string', 'max:50', 'unique:payments,receipt_number'],
            'payment_method' => ['required', 'in:Cash,Bank transfer,Other'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'confirmed' => ['accepted'],
        ]);

        try {
            DB::transaction(function () use ($request, $data, $bill): void {
                $lockedBill = Bill::lockForUpdate()->findOrFail($bill->id);
                $this->applyAmount($lockedBill, (string) $data['amount']);
                $payment = $lockedBill->payments()->create([
                    'vendor_id' => $lockedBill->vendor_id,
                    'vendor_name' => $lockedBill->vendor_name,
                    'amount' => Bill::decimal(Bill::cents((string) $data['amount'])),
                    'paid_at' => $data['paid_at'], 'receipt_number' => $data['receipt_number'],
                    'payment_method' => $data['payment_method'], 'notes' => $data['notes'] ?? null,
                    'status' => 'Paid', 'recorded_by' => $request->user()->id,
                ]);
                $this->audit($request, $lockedBill, 'payment_recorded', 'Received '.$payment->amount.' for bill #'.$lockedBill->id, $payment);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['receipt_number' => 'This receipt was already recorded. Check payment history before trying again.']);
        }

        return redirect()->route('bills.show', $bill)->with('success', 'Payment confirmed. The remaining balance has been updated.');
    }

    public function allocate(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate(['payment_id' => ['required', 'integer'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($data, $bill, $request): void {
            $lockedBill = Bill::lockForUpdate()->findOrFail($bill->id);
            $payment = Payment::where('vendor_id', $lockedBill->vendor_id)->lockForUpdate()->findOrFail($data['payment_id']);
            if ($payment->bill_id || ! in_array($payment->status, ['Paid', 'Recorded'], true)) {
                throw ValidationException::withMessages(['payment_id' => 'This receipt is already assigned or has been reversed.']);
            }
            $this->applyAmount($lockedBill, $payment->amount);
            $payment->update([
                'bill_id' => $lockedBill->id, 'status' => 'Paid',
                'recorded_by' => $payment->recorded_by ?? $request->user()->id,
            ]);
            $this->audit($request, $lockedBill, 'payment_allocated', 'Applied existing receipt '.$payment->receipt_number.' to bill #'.$lockedBill->id, $payment);
        });

        return redirect()->route('bills.show', $bill)->with('success', 'Existing receipt applied. No duplicate payment was created.');
    }

    public function reverse(Request $request, Bill $bill, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['reversal_reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $data, $bill, $payment): void {
            $lockedBill = Bill::lockForUpdate()->findOrFail($bill->id);
            $lockedPayment = Payment::where('bill_id', $lockedBill->id)->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status !== 'Paid') {
                throw ValidationException::withMessages(['reversal_reason' => 'This payment has already been reversed.']);
            }
            $lockedBill->update(['paid_amount' => Bill::decimal(Bill::cents($lockedBill->paid_amount) - Bill::cents($lockedPayment->amount))]);
            $lockedPayment->update([
                'status' => 'Reversed', 'reversed_at' => now(),
                'reversed_by' => $request->user()->id, 'reversal_reason' => $data['reversal_reason'],
            ]);
            $this->audit($request, $lockedBill, 'payment_reversed', 'Reversed receipt '.$lockedPayment->receipt_number, $lockedPayment);
        });

        return redirect()->route('bills.show', $bill)->with('success', 'Payment reversed. The amount is outstanding again; the receipt remains in history.');
    }

    private function applyAmount(Bill $bill, string $amount): void
    {
        $centavos = Bill::cents($amount);
        if ($centavos <= 0 || $centavos > Bill::cents($bill->balance)) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount greater than zero and no more than the remaining balance of ₱'.$bill->balance.'.']);
        }
        $bill->update(['paid_amount' => Bill::decimal(Bill::cents($bill->paid_amount) + $centavos)]);
    }

    private function audit(Request $request, Bill $bill, string $action, string $description, ?Payment $payment = null): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id, 'action' => $action,
            'subject_type' => Bill::class, 'subject_id' => $bill->id,
            'description' => $description,
            'metadata' => ['payment_id' => $payment?->id, 'paid_amount' => $bill->paid_amount, 'balance' => $bill->balance, 'reversal_reason' => $payment?->reversal_reason],
        ]);
    }
}
