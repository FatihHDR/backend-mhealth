<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasUuids;

    protected $table = 'hotel';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'slug',
        'name',
        'en_description',
        'id_description',
        'location_map',
        'logo',
        'highlight_image',
        'reference_image',
    ];

    protected $casts = [
        'reference_image' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function packages()
    {
        return $this->hasMany(Package::class, 'hotel_id');
    }
}
