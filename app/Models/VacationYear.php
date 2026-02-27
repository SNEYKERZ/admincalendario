<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'allocated_days',
        'used_days',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'allocated_days' => 'float',
        'used_days' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function availableDays(): float
    {
        return $this->allocated_days - $this->used_days;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}