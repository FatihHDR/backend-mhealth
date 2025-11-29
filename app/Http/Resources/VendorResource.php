<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray($request): array
    {
        // Ensure a proper array is returned (model -> array)
        return parent::toArray($request);
    }
}
