<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product->name ?? 'Unknown Product',
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->subtotal,
            'product_id' => $this->product_id,
            'is_restocked' => (bool) $this->is_restocked,
        ];
    }
}
