<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AboutUsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'en_title' => $this->en_title,
            'id_title' => $this->id_title,
            'en_about_content' => $this->en_about_content,
            'id_about_content' => $this->id_about_content,
            'en_brand_tagline' => $this->en_brand_tagline,
            'id_brand_tagline' => $this->id_brand_tagline,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
