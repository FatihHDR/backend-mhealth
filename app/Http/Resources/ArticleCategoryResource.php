<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:sO') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:sO') : null,
            'en_category' => $this->en_category,
            'id_category' => $this->id_category,
            'en_description' => $this->en_description,
            'id_description' => $this->id_description,
        ];
    }
}
