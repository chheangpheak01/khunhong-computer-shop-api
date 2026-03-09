<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
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
            'receipt_no' => $this->receipt_no,
            'invoice_no' => $this->invoice_no,
            'order_id' => $this->order_id, 
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'financial' => [
                'subtotal' => (float) $this->subtotal,
                'tax_rate' => (float) $this->tax_rate,
                'tax_amount' => (float) $this->tax_amount,
                'discount_amount' => (float) $this->discount_amount,
                'grand_total' => (float) $this->grand_total,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'reference' => $this->payment_reference,
            ],
            'void_info' => [
                'is_voided' => (bool) $this->is_voided,
                'voided_at' => $this->voided_at?->toISOString(),
                'voided_by' => $this->voided_by,
                'void_reason' => $this->void_reason,
            ],
            'items' => ReceiptItemResource::collection($this->whenLoaded('items')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'issue_date' => $this->issue_date?->toISOString(),
            'dates' => [
                'created' => $this->created_at?->toISOString(),
                'updated' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}
