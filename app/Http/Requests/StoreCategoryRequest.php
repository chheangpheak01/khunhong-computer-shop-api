<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => 'required|string|unique:categories,name|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'boolean'
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required to organize your products.',
            'name.unique' => 'This category name already exists in the system.',
            'name.max' => 'The category name is too long (maximum 255 characters).',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Only jpeg, png, jpg, and webp formats are supported.',
            'image.max' => 'The image size must be less than 2MB.',
            'status.boolean' => 'The status field must be either active or inactive.',
        ];
    }
}
