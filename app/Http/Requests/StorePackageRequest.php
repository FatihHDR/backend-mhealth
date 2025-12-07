<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            'medical_package' => 'nullable|string',
            'entertain_package' => 'nullable|string',
            'is_medical' => 'nullable|boolean',
            'is_entertain' => 'nullable|boolean',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'image' => 'nullable|url',
            'location' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama package wajib diisi',
            'name.max' => 'Nama package maksimal 255 karakter',
            'price.numeric' => 'Harga harus berupa angka',
            'price.min' => 'Harga tidak boleh negatif',
            'image.url' => 'Image harus berupa URL yang valid',
            'spesific_gender.in' => 'Gender harus salah satu dari: all, male, female',
        ];
    }
}
