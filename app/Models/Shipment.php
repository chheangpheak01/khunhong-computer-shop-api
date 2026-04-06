<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 
        'tracking_number', 
        'carrier', 
        'ship_date', 
        'status',
        'delivered_at',
        'proof_of_delivery',
        'delivery_notes',
    ];
    protected $casts = [
        'ship_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
