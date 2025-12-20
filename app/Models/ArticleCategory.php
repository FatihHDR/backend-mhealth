<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'article_category';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'en_category',
        'id_category',
        'en_description',
        'id_description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
