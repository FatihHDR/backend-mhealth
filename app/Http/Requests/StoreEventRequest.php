<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'title' => 'required_without_all:en_title,id_title|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        foreach ($value as $item) {
                            if (!filter_var($item, FILTER_VALIDATE_URL)) {
                                return $fail('Each ' . $attribute . ' must be a valid URL.');
                            }
                        }
                    } elseif (!is_null($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        return $fail('The ' . $attribute . ' must be a valid URL or an array of URLs.');
                    }
                },
            ],
            'reference_image.*' => 'url',
            'organized_image' => 'nullable|url',
            'organized_by' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            'title.required_without_all' => 'The title field is required when en_title and id_title are not provided.',
            'start_date.required' => 'The start date is required.',
            'end_date.required' => 'The end date is required.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
