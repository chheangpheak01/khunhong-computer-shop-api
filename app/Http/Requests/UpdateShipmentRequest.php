<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
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
        $shipmentId = $this->route('shipment')->id;
        return [
            'tracking_number' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('shipments')->ignore($shipmentId),
            ],
            'carrier'   => 'sometimes|string|max:50',
            'ship_date' => 'sometimes|date|before_or_equal:now',
            'status'    => 'sometimes|string|in:in_transit,delivered',
            'proof_of_delivery' => 'sometimes|nullable|string|max:255',
            'delivery_notes'    => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_number.unique' => 'This tracking number is already in use by another shipment.',
            'status.in' => 'Invalid status. Choose from: in_transit or delivered.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tracking_number' => $this->tracking_number ? trim($this->tracking_number) : null,
            'carrier' => $this->carrier ? ucfirst(strtolower(trim($this->carrier))) : null,
        ]);
        $this->replace(array_filter($this->all(), fn($value) => !is_null($value)));
    }
}
