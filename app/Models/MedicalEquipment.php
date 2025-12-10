<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicalEquipment extends Model
{
    use HasUuids;

    protected $table = 'medical_equipment';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'slug',
        'en_title',
        'id_title',
        'en_description',
        'id_description',
        'spesific_gender',
        'highlight_image',
        'reference_image',
        'vendor_id',
        'real_price',
        'discount_price',
        'status',
    ];

    protected $casts = [
        'reference_image' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the vendor that owns the medical equipment.
     */
    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
