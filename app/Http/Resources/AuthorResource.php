<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray($request): array
    {
        if ($this->resource instanceof \Illuminate\Database\Eloquent\Model) {
            return [
                'id' => $this->resource->id,
                'created_at' => optional($this->resource->created_at)->toJson(),
                'updated_at' => optional($this->resource->updated_at)->toJson(),
                'name' => $this->resource->name,
                'jobdesc' => $this->resource->jobdesc,
                'slug' => $this->resource->slug,
            ];
        }

        return (array) $this->resource;
    }
}
