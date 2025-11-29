<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
                'author' => $model->relationLoaded('author') ? new AuthorResource($model->author) : null,
                'category' => $model->category ?? [],
                'en_content' => $model->en_content,
                'id_content' => $model->id_content,
                'status' => $model->status,
            ];
        }

        return (array) $model;
    }
}
