<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'jobdesc' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:author,slug',
            'profile_image' => 'nullable|url',
        ];
    }
}
