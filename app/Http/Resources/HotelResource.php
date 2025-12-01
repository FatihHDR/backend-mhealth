<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'en_description' => $this->en_description,
            'id_description' => $this->id_description,
            'logo' => $this->logo,
            'highlight_image' => $this->highlight_image,
            'reference_image' => $this->reference_image,
            'location_map' => $this->location_map,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
