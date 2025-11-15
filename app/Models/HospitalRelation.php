<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HospitalRelation extends Model
{
    use HasUuids;

    protected $table = 'hospital_relation';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
