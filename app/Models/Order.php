<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
   protected $fillable = [
        'invoice_no',
        'customer_name',   
        'customer_phone',  
        'shipping_address',
        'total_amount',
        'status',
        'cancelled_at'
    ];
    protected $casts = [
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];
   
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function invoice(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
