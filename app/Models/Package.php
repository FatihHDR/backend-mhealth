<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Package extends Model
{
    use HasUuids;

    protected $table = 'package';
    
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_by_day',
        'duration_by_night',
        'medical_package',
        'entertain_package',
        'is_medical',
        'is_entertain',
        'spesific_gender',
        'image',
        'location',
    ];

    protected $casts = [
        'medical_package' => 'array',
        'entertain_package' => 'array',
        'is_medical' => 'boolean',
        'is_entertain' => 'boolean',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
