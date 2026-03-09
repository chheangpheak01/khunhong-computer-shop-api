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
    // protected static function booted()
    // {
    //     static::updated(function ($order) {
    //         if ($order->wasChanged('status') && $order->status === 'cancelled') {
    //             $order->load('items.product');
    //             foreach ($order->items as $item) {
    //                 if ($item->product) {
    //                     $item->product->increment('stock', $item->quantity);
    //                 }
    //             }
    //         }
    //     });
    // }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
