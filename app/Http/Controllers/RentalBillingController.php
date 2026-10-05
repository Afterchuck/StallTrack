<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bill;
use App\Models\Rental;
use App\Models\Vendor;
use App\Notifications\RentalBillSent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RentalBillingController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'billing_date' => ['nullable', 'date_format:Y-m-d'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'cycle' => ['nullable', 'in:Monthly,Quarterly,Weekly,Bi-weekly'],
        ]);
        $date = $request->input('billing_date') ?: today()->toDateString();
        $rentals = Rental::active()->with(['vendor', 'stall'])
            ->search($request->input('search'))
            ->when($request->filled('cycle'), fn ($query) => $query->where('billing_cycle', $request->input('cycle')))
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)
            ->when($request->filled('vendor_id'), fn ($query) => $query->where('vendor_id', $request->integer('vendor_id')))
            ->orderBy('vendor_id')->orderBy('stall_id')->paginate(15)->withQueryString();
        $existingBills = Bill::whereIn('vendor_id', $rentals->pluck('vendor_id'))->get();
        $rows = $rentals->getCollection()->map(function (Rental $rental) use ($date, $existingBills): array {
            try {
                $period = $rental->billingPeriod($date);
                $bill = $existingBills->first(fn (Bill $bill): bool => ($bill->rental_id === $rental->id || $bill->rental_id === null)
                    && $bill->vendor_id === $rental->vendor_id
                    && $bill->period_start->lte($period['end']) && $bill->period_end->gte($period['start'])
                );

                return compact('rental', 'period', 'bill') + ['error' => null];
            } catch (ValidationException $exception) {
                return ['rental' => $rental, 'period' => null, 'bill' => null, 'error' => $exception->getMessage()];
            }
        });

        return view('admin.payments.rentals', [
            'rentals' => $rentals, 'rows' => $rows, 'billingDate' => $date,
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function preview(Request $request, Rental $rental): View
    {
        $data = $request->validate(['billing_date' => ['required', 'date_format:Y-m-d']]);
        $this->assertActive($rental);
        $rental->load(['vendor', 'stall']);
        $period = $rental->billingPeriod($data['billing_date']);
        $existingBill = $this->overlappingBill($rental, $period['start']->toDateString(), $period['end']->toDateString());
        $arrears = $rental->vendor->bills()->outstanding()->where('period_end', '<', $period['start'])->get()
            ->sum(fn (Bill $bill): int => Bill::cents($bill->balance));

        return view('admin.payments.preview', [
            'rental' => $rental, 'period' => $period, 'billingDate' => $data['billing_date'],
            'existingBill' => $existingBill, 'arrears' => Bill::decimal($arrears),
            'reviewToken' => $this->reviewToken($rental),
        ]);
    }

    public function send(Request $request, Rental $rental): RedirectResponse
    {
        $data = $request->validate([
            'billing_date' => ['required', 'date_format:Y-m-d'],
            'due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'review_token' => ['required', 'string'],
            'confirmed' => ['accepted'],
        ]);
        $bill = DB::transaction(function () use ($request, $rental, $data): Bill {
            $vendor = Vendor::lockForUpdate()->findOrFail($rental->vendor_id);
            $locked = Rental::with('stall')->lockForUpdate()->findOrFail($rental->id);
            $this->assertActive($locked);
            if ($locked->vendor_id !== $vendor->id || ! hash_equals($this->reviewToken($locked), $data['review_token'])) {
                throw ValidationException::withMessages(['review_token' => 'The contract changed. Open a fresh preview before sending.']);
            }
            $period = $locked->billingPeriod($data['billing_date']);
            if ($this->overlappingBill($locked, $period['start']->toDateString(), $period['end']->toDateString())) {
                throw ValidationException::withMessages(['billing_date' => 'A bill already covers this rental period. View it or send a reminder instead.']);
            }
            if ($period['partial']) {
                throw ValidationException::withMessages(['billing_date' => 'This is a shortened final period. Agree the amount and use a manual exception bill; no proration is assumed.']);
            }
            if (Bill::cents($locked->rent_amount) <= 0) {
                throw ValidationException::withMessages(['billing_date' => 'Set a positive rent amount on the contract before sending.']);
            }
            $bill = Bill::create([
                'vendor_id' => $vendor->id, 'rental_id' => $locked->id,
                'vendor_name' => $vendor->name, 'stall_number' => $locked->stall->stall_number,
                'contract_number' => $locked->contract_number,
                'period_start' => $period['start'], 'period_end' => $period['end'],
                'due_date' => $period['start']->addDays((int) $data['due_days']),
                'amount' => $locked->rent_amount, 'paid_amount' => '0.00',
                'created_by' => $request->user()->id, 'sent_at' => now(),
            ]);
            $locked->billing_due_days = (int) $data['due_days'];
            $locked->save();
            $vendor->notify(new RentalBillSent($bill));
            $this->audit($request, $bill, 'bill_sent');

            return $bill;
        });

        return redirect()->route('bills.show', $bill)->with('success', 'Bill sent to the vendor portal. No payment has been recorded.');
    }

    public function notify(Request $request, Bill $bill): RedirectResponse
    {
        $request->validate(['confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $bill): void {
            $locked = Bill::with('vendor')->lockForUpdate()->findOrFail($bill->id);
            if (Bill::cents($locked->balance) <= 0) {
                throw ValidationException::withMessages(['bill' => 'This bill is fully paid. No reminder is needed.']);
            }
            $lastDelivery = $locked->last_reminded_at ?? $locked->sent_at;
            if ($lastDelivery?->gt(now()->subDay())) {
                throw ValidationException::withMessages(['bill' => 'Wait 24 hours between notifications for the same bill.']);
            }
            $reminder = $locked->sent_at !== null;
            $locked->vendor->notify(new RentalBillSent($locked, $reminder));
            $locked->update($reminder ? ['last_reminded_at' => now()] : ['sent_at' => now()]);
            $this->audit($request, $locked, $reminder ? 'bill_reminder_sent' : 'bill_sent');
        });

        return back()->with('success', 'Notification sent to the vendor portal. The bill and its balance are unchanged.');
    }

    private function assertActive(Rental $rental): void
    {
        if ($rental->status !== 'Active') {
            throw ValidationException::withMessages(['billing_date' => 'Only active rental contracts can be billed here.']);
        }
    }

    private function overlappingBill(Rental $rental, string $start, string $end): ?Bill
    {
        return Bill::where('vendor_id', $rental->vendor_id)
            ->where(fn ($query) => $query->where('rental_id', $rental->id)->orWhereNull('rental_id'))
            ->where('period_start', '<=', $end)->where('period_end', '>=', $start)->first();
    }

    private function reviewToken(Rental $rental): string
    {
        return hash_hmac('sha256', json_encode([
            $rental->id, $rental->vendor_id, $rental->stall_id, $rental->contract_number,
            $rental->start_date->toDateString(), $rental->end_date->toDateString(),
            $rental->rent_amount, $rental->billing_cycle, $rental->status,
        ], JSON_THROW_ON_ERROR), config('app.key'));
    }

    private function audit(Request $request, Bill $bill, string $action): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id, 'action' => $action,
            'subject_type' => Bill::class, 'subject_id' => $bill->id,
            'description' => ($action === 'bill_sent' ? 'Sent bill #' : 'Sent reminder for bill #').$bill->id,
            'metadata' => ['rental_id' => $bill->rental_id, 'balance' => $bill->balance],
        ]);
    }
}
