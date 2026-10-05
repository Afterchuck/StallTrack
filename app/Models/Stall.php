<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stall extends Model
{
    use HasFactory;

    protected $fillable = ['stall_number', 'market_section', 'location', 'stall_type', 'dimensions', 'monthly_rate', 'status'];

    protected function casts(): array
    {
        return ['monthly_rate' => 'decimal:2'];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
