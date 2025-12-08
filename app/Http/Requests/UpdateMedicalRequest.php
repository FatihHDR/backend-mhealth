<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRequest extends FormRequest
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
            // Title fields (all optional for update)
            'title' => 'nullable|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            
            // Tagline fields
            'tagline' => 'nullable|string|max:500',
            'en_tagline' => 'nullable|string|max:500',
            'id_tagline' => 'nullable|string|max:500',
            
            // Images
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            
            // Duration
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            
            // Gender enum (both, male, female)
            'spesific_gender' => 'nullable|string|in:both,male,female',
            
            // Medical package content
            'content' => 'nullable|string',
            'en_medical_package_content' => 'nullable|string',
            'id_medical_package_content' => 'nullable|string',
            
            // Included items
            'included' => 'nullable|array',
            'included.*' => 'string',
            
            // Foreign keys
            'vendor_id' => 'nullable|uuid|exists:vendor,id',
            'hotel_id' => 'nullable|uuid|exists:hotel,id',
            
            // Pricing
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            
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
            'spesific_gender.in' => 'Gender must be one of: both, male, female.',
            'status.in' => 'Status must be one of: draft, published, archived.',
            'vendor_id.exists' => 'The selected vendor does not exist.',
            'reference_image.*.url' => 'Each reference image must be a valid URL.',
        ];
    }
}
