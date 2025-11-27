<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LatestPackageResource extends JsonResource
{
    public function toArray($request): array
    {
        // Ensure we always return an array. The resource may be an Eloquent model,
        // an array (for CSV controllers), or a collection. Normalize accordingly.
        if ($this->resource instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->resource->toArray();
        }

        if ($this->resource instanceof \Illuminate\Support\Collection) {
            return $this->resource->toArray();
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        // Fallback: cast to array
        return (array) $this->resource;
    }
}
