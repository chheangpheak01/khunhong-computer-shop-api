<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
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
            'order_id' => $this->order_id,
            'tracking_number' => $this->tracking_number,
            'carrier' => $this->carrier,
            'ship_date' => $this->ship_date,
            'status' => $this->status,
            'delivered_at' => $this->delivered_at?->toISOString(),
            'proof_of_delivery' => $this->proof_of_delivery,
            'delivery_notes' => $this->delivery_notes,
            'order' => new OrderResource($this->whenLoaded('order')),
            'dates' => [
                'created' => $this->created_at?->toISOString(),
                'updated' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}
