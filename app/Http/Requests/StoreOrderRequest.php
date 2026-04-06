<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:1000'
        ];
    }
    public function messages(): array
    {
        return [
            'items.required' => 'Please add at least one item to your cart.',
            'items.*.product_id.exists' => 'One of the selected products is no longer available.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'customer_name.required' => 'We need a name for the delivery.',
            'customer_phone.required' => 'A phone number is required for the courier.',
            'shipping_address.required' => 'Please provide a delivery address.',
        ];
    }
}
