<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'en_description' => $this->en_description,
            'id_description' => $this->id_description,
            'category' => $this->category,
            'location_map' => $this->location_map,
            'specialist' => $this->specialist,
            'logo' => $this->logo,
            'highlight_image' => $this->highlight_image,
            'reference_image' => $this->reference_image,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
