<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = ['vendor_id', 'stall_id', 'contract_number', 'start_date', 'end_date', 'rent_amount', 'billing_cycle', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'rent_amount' => 'decimal:2', 'billing_due_days' => 'integer'];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, partial: bool} */
    public function billingPeriod(string $billingDate): array
    {
        $date = CarbonImmutable::parse($billingDate)->startOfDay();
        $anchor = CarbonImmutable::instance($this->start_date);
        $contractEnd = CarbonImmutable::instance($this->end_date);
        if ($date->lt($anchor) || $date->gt($contractEnd)) {
            throw ValidationException::withMessages(['billing_date' => 'Choose a date within this rental contract.']);
        }
        if (in_array($this->billing_cycle, ['Weekly', 'Bi-weekly'], true)) {
            $days = $this->billing_cycle === 'Weekly' ? 7 : 14;
            $index = intdiv((int) $anchor->diffInDays($date), $days);
            $start = $anchor->addDays($index * $days);
            $end = $start->addDays($days - 1);
        } elseif (in_array($this->billing_cycle, ['Monthly', 'Quarterly'], true)) {
            $months = $this->billing_cycle === 'Monthly' ? 1 : 3;
            $difference = ($date->year - $anchor->year) * 12 + $date->month - $anchor->month;
            $index = intdiv($difference, $months);
            if ($anchor->addMonthsNoOverflow($index * $months)->gt($date)) {
                $index--;
            }
            $start = $anchor->addMonthsNoOverflow($index * $months);
            $end = $anchor->addMonthsNoOverflow(($index + 1) * $months)->subDay();
        } else {
            throw ValidationException::withMessages(['billing_date' => 'This rental has an unsupported billing cycle. Update the contract first.']);
        }

        return ['start' => $start, 'end' => $end->min($contractEnd), 'partial' => $end->gt($contractEnd)];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! filled($search)) {
            return $query;
        }
        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search)).'%';

        return $query->where(fn (Builder $rentals) => $rentals->whereRaw("contract_number LIKE ? ESCAPE '!'", [$term])
            ->orWhereHas('vendor', fn (Builder $vendors) => $vendors->whereRaw("name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("email LIKE ? ESCAPE '!'", [$term]))
            ->orWhereHas('stall', fn (Builder $stalls) => $stalls->whereRaw("stall_number LIKE ? ESCAPE '!'", [$term])->orWhereRaw("market_section LIKE ? ESCAPE '!'", [$term])));
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->active()
            ->whereBetween('end_date', [today(), today()->addDays($days)]);
    }
}
