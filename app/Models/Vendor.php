<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasUuids;

    protected $table = 'vendor';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'slug',
        'name',
        'en_description',
        'id_description',
        'category',
        'location_map',
        'specialist',
        'logo',
        'highlight_image',
        'reference_image',
    ];

    protected $casts = [
        'specialist' => 'array',
        'reference_image' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function packages()
    {
        return $this->hasMany(Packages::class, 'vendor_id');
    }
}
