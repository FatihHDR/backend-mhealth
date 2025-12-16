<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalEquipmentRequest extends FormRequest
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
            // Title fields (required)
            'title' => 'required_without_all:en_title,id_title|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            
            // Description fields
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            
            // Images
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            
            // Gender enum (both, male, female)
            'spesific_gender' => 'nullable|string|in:both,male,female',
            
            // Foreign key
            'vendor_id' => 'nullable|uuid|exists:vendor,id',
            
            // Pricing (DB stores as text) - accept string or numeric and enforce length
            'real_price' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_null($value)) return;
                    if (!is_string($value) && !is_numeric($value)) {
                        return $fail('The ' . $attribute . ' must be a string or numeric.');
                    }
                    if (mb_strlen((string) $value) > 100) {
                        return $fail('The ' . $attribute . ' must not exceed 100 characters.');
                    }
                },
            ],
            'discount_price' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_null($value)) return;
                    if (!is_string($value) && !is_numeric($value)) {
                        return $fail('The ' . $attribute . ' must be a string or numeric.');
                    }
                    if (mb_strlen((string) $value) > 100) {
                        return $fail('The ' . $attribute . ' must not exceed 100 characters.');
                    }
                },
            ],
            
            // Status enum
            'status' => 'nullable|string|in:draft,published,archived',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required_without_all' => 'Either title, en_title, or id_title is required.',
            'spesific_gender.in' => 'Gender must be one of: both, male, female.',
            'status.in' => 'Status must be one of: draft, published, archived.',
            'reference_image.*.url' => 'Each reference image must be a valid URL.',
        ];
    }
}
