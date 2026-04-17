<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use Tenantable;

    protected $fillable = [
        'tenant_id',
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
