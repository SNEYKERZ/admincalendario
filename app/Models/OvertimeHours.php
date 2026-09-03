<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeHours extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'date',
        'day_of_week',
        'day_of_month',
        'month',
        'start_time',
        'end_time',
        'client',
        'project',
        'reason',
        'hours',
        'status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'exceeds_daily_limit',
        'exceeds_weekly_limit',
        'respects_company_schedule',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'float',
        'approved_at' => 'datetime',
        'exceeds_daily_limit' => 'boolean',
        'exceeds_weekly_limit' => 'boolean',
        'respects_company_schedule' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withoutGlobalScopes();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
