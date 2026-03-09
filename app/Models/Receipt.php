<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ReceiptItem;

class Receipt extends Model
{
    protected $fillable = [
        'order_id',
        'receipt_no',
        'invoice_no',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'payment_method',
        'payment_status',
        'payment_reference',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
        'issue_date',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'is_voided' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }
   
}

