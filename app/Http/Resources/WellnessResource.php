<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WellnessResource extends JsonResource
{
    public function toArray($request): array
    {
        return $this->resource;
    }
}
