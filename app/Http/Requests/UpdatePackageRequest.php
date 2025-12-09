<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
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
            
            // Tagline fields
            'tagline' => 'nullable|string|max:500',
            'en_tagline' => 'nullable|string|max:500',
            'id_tagline' => 'nullable|string|max:500',
            
            // Detail fields
            'detail' => 'nullable|string',
            'en_detail' => 'nullable|string',
            'id_detail' => 'nullable|string',
            
            // Images
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            
            // Duration
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            
            // Gender enum (both, male, female)
            'spesific_gender' => 'nullable|string|in:both,male,female',
            
            // Package content
            'medical_content' => 'nullable|string',
            'en_medical_package_content' => 'nullable|string',
            'id_medical_package_content' => 'nullable|string',
            'wellness_content' => 'nullable|string',
            'en_wellness_package_content' => 'nullable|string',
            'id_wellness_package_content' => 'nullable|string',
            
            // Included items
            'included' => 'nullable|array',
            'included.*' => 'string',
            
            // Foreign keys
            'vendor_id' => 'nullable|uuid|exists:vendor,id',
            'hotel_id' => 'nullable|uuid|exists:hotel,id',
            
            // Pricing
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            
            // Status enum (draft, published, archived)
            'status' => 'nullable|string|in:draft,published,archived',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'en_title.max' => 'English title maksimal 255 karakter',
            'id_title.max' => 'Indonesian title maksimal 255 karakter',
            'highlight_image.url' => 'Highlight image harus berupa URL yang valid',
            'reference_image.*.url' => 'Setiap reference image harus berupa URL yang valid',
            'spesific_gender.in' => 'Gender harus salah satu dari: both, male, female',
            'vendor_id.exists' => 'Vendor tidak ditemukan',
            'hotel_id.exists' => 'Hotel tidak ditemukan',
            'status.in' => 'Status harus salah satu dari: draft, published, archived',
        ];
    }
}
