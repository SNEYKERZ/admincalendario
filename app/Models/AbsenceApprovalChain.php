<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceApprovalChain extends Model
{
    protected $fillable = [
        'absence_id',
        'approval_level',
        'assigned_to',
        'status',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'completed_at' => 'datetime',
    ];

    const LEVEL_AREA_MANAGER = 1;
    const LEVEL_ADMIN = 2;

    public function absence(): BelongsTo
    {
        return $this->belongsTo(Absence::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }
}
