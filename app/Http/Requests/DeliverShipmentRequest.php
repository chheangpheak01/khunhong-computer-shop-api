<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliverShipmentRequest extends FormRequest
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
            'proof_of_delivery' => 'nullable|string|max:255',
            'delivery_notes'    => 'nullable|string|max:500',
            'delivered_at'      => 'nullable|date|before_or_equal:now', 
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'proof_of_delivery.max' => 'The proof of delivery reference is too long (maximum 255 characters).',
            'delivery_notes.max'    => 'The delivery notes are too long (maximum 500 characters).',
            'delivered_at.date'     => 'Please provide a valid delivery date and time.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'proof_of_delivery' => $this->proof_of_delivery ? trim($this->proof_of_delivery) : null,
            'delivery_notes'    => $this->delivery_notes ? trim($this->delivery_notes) : null,
        ]);
    }
}
