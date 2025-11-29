<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use HasUuids;

    protected $table = 'about_us';
    public $timestamps = true;

    protected $fillable = [
        'en_title',
        'id_title',
        'en_about_content',
        'id_about_content',
        'en_brand_tagline',
        'id_brand_tagline',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
