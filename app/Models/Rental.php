<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rental extends Model
{
    protected $fillable = ['vendor_id', 'stall_id', 'contract_number', 'start_date', 'end_date', 'rent_amount', 'billing_cycle', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'rent_amount' => 'decimal:2'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class);
    }
}
