<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'days_before',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'days_before' => 'integer',
    ];
}
