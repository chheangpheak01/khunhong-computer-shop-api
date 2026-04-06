<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
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
            'tracking_number' => 'required|string|unique:shipments|max:100',
            'carrier'  => 'required|string|max:50',
            'ship_date'  => 'required|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tracking_number.required' => 'A tracking number is required.',
            'tracking_number.unique'   => 'This tracking number already exists in the system.',
            'carrier.required'         => 'Please specify the shipping carrier.',
            'ship_date.required'       => 'Please provide the shipping date.',
            'ship_date.date'           => 'Please provide a valid shipping date.',
            'ship_date.after_or_equal' => 'The shipping date cannot be in the past.', 
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tracking_number' => trim($this->tracking_number),
            'carrier' => ucfirst(strtolower(trim($this->carrier ?? ''))),
        ]);
    }
}
