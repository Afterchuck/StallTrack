<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'name', 'stall_number', 'contract_until', 'status', 'contact_number',
        'email', 'residential_address', 'photo_path', 'market_section',
        'monthly_rent', 'billing_cycle', 'contract_start_date', 'contract_end_date',
    ];

    protected function casts(): array
    {
        return [
            'contract_until' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'monthly_rent' => 'decimal:2',
        ];
    }
}
