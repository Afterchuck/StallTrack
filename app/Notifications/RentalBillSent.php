<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Notifications\Notification;

class RentalBillSent extends Notification
{
    public function __construct(public Bill $bill, public bool $reminder = false) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{bill_id: int, title: string, stall_number: ?string, period: string, balance: string, due_date: string} */
    public function toArray(object $notifiable): array
    {
        return [
            'bill_id' => $this->bill->id,
            'title' => $this->reminder ? 'Rent payment reminder' : 'Your rental bill is ready',
            'stall_number' => $this->bill->stall_number,
            'period' => $this->bill->period_start->format('M d, Y').' – '.$this->bill->period_end->format('M d, Y'),
            'balance' => $this->bill->balance,
            'due_date' => $this->bill->due_date->toDateString(),
        ];
    }
}
