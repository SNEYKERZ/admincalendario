<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\AbsenceAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function logAction(
        Absence $absence,
        string $action,
        array $changes = [],
        ?string $reason = null
    ): AbsenceAudit {
        return AbsenceAudit::create([
            'absence_id' => $absence->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => !empty($changes) ? $changes : null,
            'reason' => $reason,
            'ip_address' => Request::ip(),
        ]);
    }
}
