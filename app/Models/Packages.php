<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Packages extends Model
{
    use HasUuids;

    protected $table = 'packages';
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
        'en_medical_package_content',
        'id_medical_package_content',
        'en_wellness_package_content',
        'id_wellness_package_content',
        'included',
        'vendor_id',
        'hotel_id',
        'real_price',
        'discount_price',
        'status',
    ];

    protected $casts = [
        'reference_image' => 'array',
        'included' => 'array',
        'duration_by_day' => 'integer',
        'duration_by_night' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the vendor that owns the package.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the hotel that owns the package.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
