<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')->id;
            return [
                'name' => 'sometimes|required|string|max:255|unique:categories,name,' .  $categoryId,
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'status' => 'boolean'
            ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'The category name can not be empty when updating.',
            'name.unique'   => 'This category name is already taken by another category.',
            'name.max'      => 'The category name is too long.',
            'image.image'    => 'The uploaded file must be an image.',
            'image.mimes'    => 'Only jpeg, png, jpg, and webp formats are supported.', 
            'image.max'      => 'The image size is too large (Maximum 2MB).',
            'status.boolean' => 'The status must be true or false.',
        ];
    }
}
