<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WellnessPackageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'en_title' => $this->en_title,
            'id_title' => $this->id_title,
            'en_tagline' => $this->en_tagline,
            'id_tagline' => $this->id_tagline,
            'highlight_image' => $this->highlight_image,
            'reference_image' => $this->reference_image,
            'duration_by_day' => $this->duration_by_day,
            'duration_by_night' => $this->duration_by_night,
            'spesific_gender' => $this->spesific_gender,
            'en_wellness_package_content' => $this->en_wellness_package_content,
            'id_wellness_package_content' => $this->id_wellness_package_content,
            'included' => $this->included,
            'hotel_id' => $this->hotel_id,
            'real_price' => $this->real_price,
            'discount_price' => $this->discount_price,
            'status' => $this->status,
            'hotel' => $this->whenLoaded('hotel'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
