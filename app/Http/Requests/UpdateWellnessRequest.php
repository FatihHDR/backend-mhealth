<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWellnessRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'en_tagline' => 'nullable|string|max:255',
            'id_tagline' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'en_wellness_package_content' => 'nullable|string',
            'id_wellness_package_content' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            'spesific_gender' => 'nullable|string|in:both,male,female',
            'included' => 'nullable|array',
            'included.*' => 'string',
            'hotel_id' => 'nullable|uuid',
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:draft,published,archived',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Judul maksimal 255 karakter',
            'en_title.max' => 'English title maksimal 255 karakter',
            'id_title.max' => 'Indonesian title maksimal 255 karakter',
            'highlight_image.url' => 'Highlight image harus berupa URL yang valid',
            'reference_image.*.url' => 'Setiap reference image harus berupa URL yang valid',
            'hotel_id.uuid' => 'Hotel ID harus berupa UUID yang valid',
            'real_price.numeric' => 'Real price harus berupa angka',
            'discount_price.numeric' => 'Discount price harus berupa angka',
            'spesific_gender.in' => 'Gender harus salah satu dari: both, male, female',
            'status.in' => 'Status harus salah satu dari: draft, published, archived',
        ];
    }
}
