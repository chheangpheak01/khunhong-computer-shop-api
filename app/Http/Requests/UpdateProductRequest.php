<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'category_id' => 'sometimes|exists:categories,id',
            'name'        => 'sometimes|string|max:255',
            'brand'       => 'sometimes|string|max:100',
            'price'       => 'sometimes|numeric|min:0', 
            'stock'       => 'sometimes|integer|min:0',
            'status'      => 'sometimes|boolean',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category is invalid.',
            'name.string'        => 'The product name must be a valid text string.',
            'price.numeric'      => 'The price must be a number.',
            'price.min'          => 'The price can not be lower than 0.',
            'stock.integer'      => 'The stock must be a whole number.',
            'stock.min'          => 'The stock can not be negative.',
            'image.image'        => 'The uploaded file must be an image.',
            'image.mimes'        => 'Only jpeg, png, jpg, and webp formats are supported.', 
            'image.max'          => 'The image size is too large (Maximum 2MB).',
        ];
    }
}
