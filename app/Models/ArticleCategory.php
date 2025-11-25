<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    use HasUuids;

    protected $table = 'article_category';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'en_category',
        'id_category',
        'en_description',
        'id_description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'udpated_at' => 'datetime',
    ];
}
