<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicalTech extends Model
{
    use HasUuids;

    protected $table = 'medical_tech';

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
