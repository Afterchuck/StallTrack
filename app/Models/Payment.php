<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (blank($payment->receipt_number)) {
                $payment->receipt_number = 'RCT-'.Str::ulid();
            }
        });
    }

    protected $fillable = [
        'vendor_id', 'vendor_name', 'amount', 'paid_at', 'receipt_number', 'status',
        'bill_id', 'payment_method', 'notes', 'recorded_by', 'reversed_by', 'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date', 'reversed_at' => 'datetime'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
