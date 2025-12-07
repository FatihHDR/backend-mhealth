<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutUsRequest extends FormRequest
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
            // Title fields
            'title' => 'nullable|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            
            // About content fields
            'about_content' => 'nullable|string',
            'en_about_content' => 'nullable|string',
            'id_about_content' => 'nullable|string',
            
            // Brand tagline fields
            'brand_tagline' => 'nullable|string|max:500',
            'en_brand_tagline' => 'nullable|string|max:500',
            'id_brand_tagline' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'en_title.max' => 'English title cannot exceed 255 characters.',
            'id_title.max' => 'Indonesian title cannot exceed 255 characters.',
            'en_brand_tagline.max' => 'English brand tagline cannot exceed 500 characters.',
            'id_brand_tagline.max' => 'Indonesian brand tagline cannot exceed 500 characters.',
        ];
    }
}
