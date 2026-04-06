<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InvoiceDetail;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_no',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'issue_date',   
    ];

     protected $casts = [
        'issue_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(InvoiceDetail::class);
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }   
}

