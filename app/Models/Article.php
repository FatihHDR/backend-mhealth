<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'article';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'slug',
        'en_title',
        'id_title',
        'author',
        'category',
        'en_content',
        'id_content',
        'status',
    ];

    protected $casts = [
        'category' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author');
    }
}
