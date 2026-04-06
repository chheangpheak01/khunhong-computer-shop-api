<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
     public function toArray(Request $request): array
     {
        $payment = $this->order->payment ?? null;

        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'order_id' => $this->order_id, 
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
                'address' => $this->shipping_address,
            ],
            'financial' => [
                'subtotal' => (float) $this->subtotal,
                'tax_rate' => (float) $this->tax_rate,
                'tax_amount' => (float) $this->tax_amount,
                'discount_amount' => (float) $this->discount_amount,
                'grand_total' => (float) $this->grand_total,
            ],
            'payment' => [
                'method' => $payment?->method,
                'status' => $payment?->status,
                'reference' => $payment?->reference_no,
                'amount' => $payment?->amount ? (float) $payment->amount : null,
                'date' => $payment?->payment_date?->toISOString(),
            ],
            'void_info' => [
                'is_voided' => $payment?->is_voided ?? false,
                'voided_at' => $payment?->voided_at?->toISOString(),
                'voided_by' => $payment?->voided_by,
                'void_reason' => $payment?->void_reason,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('order', function() {
                return $this->order->items;
            })),
            'order' => new OrderResource($this->whenLoaded('order')),
            'issue_date' => $this->issue_date?->toISOString(),
            'dates' => [
                'created' => $this->created_at?->toISOString(),
                'updated' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}
