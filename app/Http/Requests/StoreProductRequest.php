<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'name'        => 'required|string|max:255|unique:products,name',
            'brand'       => 'required|string|max:100',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'      => 'sometimes|boolean',
        ];
    }
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category is invalid or no longer exists.',
            'name.required'      => 'A product name is required.',
            'name.unique'        => 'This product name is already in your inventory.',
            'brand.required'     => 'Please specify the brand (e.g., ASUS, MSI, Logitech).',
            'price.required'     => 'The product must have a price.',
            'price.numeric'      => 'The price must be a valid number.',
            'price.min'          => 'Price can not be a negative value.',
            'stock.integer'      => 'Stock quantity must be a whole number.',
            'stock.min'          => 'Stock cannot be less than zero.',
            'image.image'        => 'The file must be an image.',
            'image.mimes'        => 'Only jpeg, png, jpg, and webp formats are supported.',
            'image.max'          => 'The image size must be less than 2MB.',
        ];
    }
}
