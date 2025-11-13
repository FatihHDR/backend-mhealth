<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ErrorLog extends Model
{
    use HasUuids;

    protected $table = 'errors';
    
    protected $fillable = [
        'error_code',
        'error_message',
        'stack_trace',
        'request_url',
        'request_method',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
