<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ChatActivity extends Model
{
    use HasUuids;

    protected $table = 'chat_activity';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'chat_activity_data',
        'public_id',
        'user_id',
        'share_slug',
        'status',
    ];

    protected $casts = [
        'chat_activity_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
