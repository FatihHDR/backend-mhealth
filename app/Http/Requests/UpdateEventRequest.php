<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|url',
            'organized_image' => 'nullable|url',
            'organized_by' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_name' => 'nullable|string|max:255',
            'location_map' => 'nullable|url',
            'status' => 'nullable|string|in:draft,published,archived',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
