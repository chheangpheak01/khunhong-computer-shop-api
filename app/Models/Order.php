<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
   protected $fillable = [
        'invoice_no',
        'customer_name',
        'total_amount',
        'status'
    ];
   
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
