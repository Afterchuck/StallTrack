<?php

namespace App\Models;

use Database\Factories\VendorSupportRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSupportRequest extends Model
{
    /** @use HasFactory<VendorSupportRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'subject', 'category', 'message', 'status', 'admin_response', 'responded_by', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
