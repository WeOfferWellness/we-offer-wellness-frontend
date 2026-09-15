<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuidePage extends Model
{
    protected $table = 'guide_pages';

    protected $casts = [
        'sections' => 'array',
        'faqs' => 'array',
        'published_at' => 'datetime',
    ];
}
