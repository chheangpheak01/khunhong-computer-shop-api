<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'discount_amount' => is_numeric($this->discount_amount) ? $this->discount_amount : 0,
            'payment_method' => strtolower($this->payment_method ?? 'cash'),
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_method'    => 'required|string|in:cash,credit_card,bank_transfer,qr_pay|max:50',
            'payment_reference' => 'required_unless:payment_method,cash|nullable|string|max:100',
            'discount_amount'   => 'required|numeric|min:0|max:999999.99', 
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required'  => 'Please select a payment method.',
            'payment_reference.required_unless' => 'A reference number is required for digital payments.', 
            'discount_amount.min' => 'Discount cannot be a negative value.',
        ];
    }
}




