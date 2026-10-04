<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['vendor_id', 'vendor_name', 'amount', 'due_date', 'paid_at', 'receipt_number', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date', 'paid_at' => 'date'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
