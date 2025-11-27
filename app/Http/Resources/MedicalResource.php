<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicalResource extends JsonResource
{
    public function toArray($request): array
    {
        // Ensure we always return an array. The resource may be an Eloquent model,
        // a collection, or a plain array. Normalize accordingly to satisfy
        // the JsonResource contract which expects an array return type.
        if ($this->resource instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->resource->toArray();
        }

        if ($this->resource instanceof \Illuminate\Support\Collection) {
            return $this->resource->toArray();
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        return (array) $this->resource;
    }
}
