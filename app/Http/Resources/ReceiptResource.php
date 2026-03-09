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
                'method' => $this->payment?->method,
                'status' => $this->payment?->status,
                'reference' => $this->payment?->reference_no,
                'amount' => $this->payment?->amount ? (float) $this->payment->amount : null,
                'date' => $this->payment?->payment_date?->toISOString(),
            ],
            'void_info' => [
                'is_voided' => $this->payment?->is_voided ?? false,
                'voided_at' => $this->payment?->voided_at?->toISOString(),
                'voided_by' => $this->payment?->voided_by,
                'void_reason' => $this->payment?->void_reason,
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
