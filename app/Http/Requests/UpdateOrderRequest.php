<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'customer_name' => 'sometimes|string|max:255',
            'status' => 'required|string|in:pending,completed,cancelled',
        ];
    }
    public function messages(): array
    {
        return [
            'status.in' => 'The status must be either pending, completed, or cancelled.',
            'status.required' => 'Please provide a status update for this order.',
        ];
    }
}
