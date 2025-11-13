<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecomendationPackage extends Model
{
    use HasUuids;

    protected $table = 'recomendation_package';
    
    protected $fillable = [
        'name',
        'description',
        'image',
        'category',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
