<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceAudit extends Model
{
    protected $fillable = [
        'absence_id',
        'user_id',
        'action',
        'changes',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function absence(): BelongsTo
    {
        return $this->belongsTo(Absence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
