<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasUuids;

    protected $table = 'accounts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'email',
        'fullname',
        'phone',
        'gender',
        'domicile',
        'height',
        'weight',
        'avatar_url',
        'birthdate',
    ];

    protected $casts = [
        'domicile' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
