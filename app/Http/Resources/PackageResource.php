<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_by_day' => $this->duration_by_day,
            'duration_by_night' => $this->duration_by_night,
            'medical_package' => $this->medical_package,
            'entertain_package' => $this->entertain_package,
            'is_medical' => (bool) $this->is_medical,
            'is_entertain' => (bool) $this->is_entertain,
            'spesific_gender' => $this->spesific_gender ?? 'all',
            'image' => $this->image,
            'location' => $this->location,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
