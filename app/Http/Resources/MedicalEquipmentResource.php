<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicalEquipmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'en_title' => $this->en_title,
            'id_title' => $this->id_title,
            'en_description' => $this->en_description,
            'id_description' => $this->id_description,
            'spesific_gender' => $this->spesific_gender,
            'highlight_image' => $this->highlight_image,
            'reference_image' => $this->reference_image,
            'real_price' => $this->real_price,
            'discount_price' => $this->discount_price,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
