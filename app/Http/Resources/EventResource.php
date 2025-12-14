<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        $model = $this->resource;

        if ($model instanceof \Illuminate\Database\Eloquent\Model) {
            return [
                'id' => $model->id,
                'created_at' => optional($model->created_at)->toJson(),
                'updated_at' => optional($model->updated_at)->toJson(),
                'slug' => $model->slug,
                'en_title' => $model->en_title,
                'id_title' => $model->id_title,
                'en_description' => $model->en_description,
                'id_description' => $model->id_description,
                'highlight_image' => $model->highlight_image,
                'reference_image' => $model->reference_image,
                'organized_image' => $model->organized_image,
                'organized_by' => $model->organized_by,
                'start_date' => optional($model->start_date)->toJson(),
                'end_date' => optional($model->end_date)->toJson(),
                'location_name' => $model->location_name,
                'location_map' => $model->location_map,
                'status' => $model->status,
            ];
        }

        return (array) $model;
    }
}
