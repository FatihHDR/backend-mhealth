<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Packages extends Model
{
    use HasUuids;

    protected $table = 'packages';
    protected $keyType = 'string';
    public $incrementing = false;

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
        'reference_image' => 'array',
        'included' => 'array',
        'real_price' => 'string',
        'discount_price' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
