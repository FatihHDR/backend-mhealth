<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Wellness extends Model
{
    use HasUuids;

    protected $table = 'wellness';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'slug',
        'en_title',
        'id_title',
        'en_tagline',
        'id_tagline',
        'highlight_image',
        'reference_image',
        'duration_by_day',
        'duration_by_night',
        'spesific_gender',
        'en_wellness_package_content',
        'id_wellness_package_content',
        'included',
        'hotel_id',
        'real_price',
        'discount_price',
        'status',
    ];

    protected $casts = [
        'reference_image' => 'array',
        'included' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
