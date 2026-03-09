<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
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
            'discount_amount' => $this->discount_amount ?? 0,
            'payment_method' => strtolower($this->payment_method ?? 'cash'),
            'payment_status' => $this->payment_status ?? 'paid',
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_method'    => 'required|string|in:cash,credit_card,bank_transfer,qr_pay|max:50',
            'payment_reference' => 'required_if:payment_method,bank_transfer,qr_pay|nullable|string|max:100',
            'discount_amount'   => 'nullable|numeric|min:0|max:999999.99', 
            'customer_email'    => 'nullable|email|max:255',
            'customer_phone'    => 'nullable|string|max:20',
            'payment_status'    => 'nullable|string|in:paid,pending,failed',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required'    => 'Please select a payment method.',
            'payment_reference.required_if' => 'A reference number is required for digital payments.',
            'customer_email.email'  => 'Please provide a valid email address.',
            'discount_amount.min'  => 'Discount cannot be a negative value.',
        ];
    }
}




