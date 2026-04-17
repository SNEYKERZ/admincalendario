<?php

namespace App\Models;

use App\Enums\AbsenceStatus;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'user_id',
        'absence_type_id',
        'start_datetime',
        'end_datetime',
        'include_saturday',
        'include_sunday',
        'include_holidays',
        'holiday_country',
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
        'include_saturday' => 'boolean',
        'include_sunday' => 'boolean',
        'include_holidays' => 'boolean',
        'total_days' => 'float',
        'total_hours' => 'float',
        'status' => AbsenceStatus::class,
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
        return $query->where('status', AbsenceStatus::APPROVED->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', AbsenceStatus::PENDING->value);
    }

    public function scopeForCalendar($query)
    {
        return $query->where('status', AbsenceStatus::APPROVED->value);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->status instanceof AbsenceStatus
            ? $this->status === AbsenceStatus::APPROVED
            : $this->status === AbsenceStatus::APPROVED->value;
    }

    public function isPending(): bool
    {
        return $this->status instanceof AbsenceStatus
            ? $this->status === AbsenceStatus::PENDING
            : $this->status === AbsenceStatus::PENDING->value;
    }

    public function isRejected(): bool
    {
        return $this->status instanceof AbsenceStatus
            ? $this->status === AbsenceStatus::REJECTED
            : $this->status === AbsenceStatus::REJECTED->value;
    }
}
