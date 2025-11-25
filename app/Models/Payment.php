<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $table = 'payment_records';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'transaction_id',
        'fullname',
        'email',
        'phone',
        'address',
        'payment_status',
        'packages_id',
        'medical_id',
        'wellness_id',
        'consultation_id',
        'medical_equipment_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
