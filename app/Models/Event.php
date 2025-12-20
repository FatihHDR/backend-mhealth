<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'events';

    protected $fillable = [
        'slug',
        'en_title',
        'id_title',
        'en_description',
        'id_description',
        'highlight_image',
        'reference_image',
        'organized_image',
        'organized_by',
        'start_date',
        'end_date',
        'location_name',
        'location_map',
        'status',
        'registration_url'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'reference_image' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
