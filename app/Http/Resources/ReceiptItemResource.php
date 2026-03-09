<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptItemResource extends JsonResource
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
            'receipt_id' => $this->receipt_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->subtotal, 
            'product' => new ProductResource($this->whenLoaded('product')),
            'dates' => [
                'created' => $this->created_at?->toISOString(),
                'updated' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}
