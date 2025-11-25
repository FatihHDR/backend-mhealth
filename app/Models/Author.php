<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasUuids;

    protected $table = 'author';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'jobdesc',
        'slug',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'author');
    }
}
