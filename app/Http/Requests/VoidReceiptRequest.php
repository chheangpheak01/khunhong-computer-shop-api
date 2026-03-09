<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidReceiptRequest extends FormRequest
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
            'reason' => 'required|string|max:255',
            'restore_stock' => 'sometimes|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required to void this receipt.',
            'reason.max' => 'The reason is too long (maximum 255 characters).',
            'restore_stock.boolean' => 'The restore_stock field must be true or false.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'restore_stock' => $this->restore_stock ?? false,
        ]);
    }
}
