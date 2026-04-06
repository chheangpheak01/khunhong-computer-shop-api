<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'receipt_id',
        'method',
        'amount',
        'status',
        'reference_no',
        'payment_date',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'voided_at' => 'datetime',
        'is_voided' => 'boolean',
        'amount' => 'decimal:2',
    ];
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
