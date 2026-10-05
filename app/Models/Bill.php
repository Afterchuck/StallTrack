<?php

namespace App\Models;

use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'vendor_name', 'stall_number', 'period_start', 'period_end',
        'due_date', 'amount', 'paid_amount', 'created_by',
        'rental_id', 'contract_number', 'sent_at', 'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date',
            'amount' => 'decimal:2', 'paid_amount' => 'decimal:2',
            'sent_at' => 'datetime', 'last_reminded_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereColumn('paid_amount', '<', 'amount');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }
        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search)).'%';

        return $query->where(fn (Builder $bills) => $bills->whereRaw("vendor_name LIKE ? ESCAPE '!'", [$term])
            ->orWhereRaw("stall_number LIKE ? ESCAPE '!'", [$term])
            ->orWhereRaw("contract_number LIKE ? ESCAPE '!'", [$term]));
    }

    public function getBalanceAttribute(): string
    {
        return self::decimal(self::cents($this->amount) - self::cents($this->paid_amount ?? '0'));
    }

    public function getStatusAttribute(): string
    {
        if (self::cents($this->balance) === 0) {
            return 'Paid';
        }

        return self::cents($this->paid_amount ?? '0') > 0 ? 'Partially paid' : 'Unpaid';
    }

    public function getIsOverdueAttribute(): bool
    {
        return self::cents($this->balance) > 0 && $this->due_date->lt(today());
    }

    /**
     * Convert validated nonnegative decimal money to integer centavos.
     */
    public static function cents(string $amount): int
    {
        $sign = str_starts_with($amount, '-') ? -1 : 1;
        [$whole, $fraction] = array_pad(explode('.', ltrim($amount, '-'), 2), 2, '');

        return $sign * (((int) $whole * 100) + (int) str_pad($fraction, 2, '0'));
    }

    public static function decimal(int $centavos): string
    {
        return ($centavos < 0 ? '-' : '').intdiv(abs($centavos), 100).'.'.str_pad((string) (abs($centavos) % 100), 2, '0', STR_PAD_LEFT);
    }
}
