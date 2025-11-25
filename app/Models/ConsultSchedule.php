<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConsultSchedule extends Model
{
    use HasUuids;

    protected $table = 'consult_schedule';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'fullname',
        'complaint',
        'date_of_birth',
        'height',
        'weight',
        'gender',
        'location',
        'scheduled_date',
        'scheduled_time',
        'payment_status',
    ];

    protected $casts = [
        'location' => 'array',
        'scheduled_date' => 'datetime',
        'scheduled_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
