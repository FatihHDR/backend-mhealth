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
    protected $attributes = [
        'status' => 'private',
    ];

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

    protected static function booted()
    {
        static::saving(function ($chat) {
            if ($chat->status === 'public') {
                if (empty($chat->share_slug)) {
                    $chat->share_slug = \Illuminate\Support\Str::random(16);
                }
            } else {
                $chat->share_slug = null;
            }
        });
    }
}
