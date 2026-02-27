<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'absence_type_id',
        'start_datetime',
        'end_datetime',
        'total_days',
        'total_hours',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'total_days' => 'float',
        'total_hours' => 'float',
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(AbsenceType::class, 'absence_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeForCalendar($query)
    {
        return $query->where('status', 'aprobado');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }
}