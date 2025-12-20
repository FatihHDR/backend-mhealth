<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $authorId = $this->route('author');

        return [
            'name' => 'sometimes|string|max:255',
            'jobdesc' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:author,slug,' . $authorId,
            'profile_image' => 'nullable|url',
        ];
    }
}
