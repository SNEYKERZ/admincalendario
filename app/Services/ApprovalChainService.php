<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\User;
use App\Enums\AbsenceStatus;
use Illuminate\Support\Facades\DB;

class ApprovalChainService
{
    public function createApprovalChain(Absence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $user = $absence->user;

            // Nivel 1: Jefe de Área
            if ($user->area && $user->area->hasManager()) {
                AbsenceApprovalChain::create([
                    'absence_id' => $absence->id,
                    'approval_level' => AbsenceApprovalChain::LEVEL_AREA_MANAGER,
                    'assigned_to' => $user->area->area_manager_id,
                    'status' => 'pendiente',
                ]);
            }

            // Nivel 2: Admin (siempre, para auditoría)
            if ($absence->type->requires_approval) {
                $admin = User::admins()->first();
                if ($admin) {
                    AbsenceApprovalChain::create([
                        'absence_id' => $absence->id,
                        'approval_level' => AbsenceApprovalChain::LEVEL_ADMIN,
                        'assigned_to' => $admin->id,
                        'status' => 'pendiente',
                    ]);
                }
            }
        });
    }

    public function approve(
        AbsenceApprovalChain $chain,
        User $approver,
        ?string $notes = null
    ): AbsenceApprovalChain {
        return DB::transaction(function () use ($chain, $approver, $notes) {
            $chain->update([
                'status' => 'aprobado',
                'assigned_to' => $approver->id,
                'notes' => $notes,
                'completed_at' => now(),
            ]);

            $chain->refresh();

            // Verificar si hay más niveles pendientes
            $nextPending = $chain->absence->approvalChains()
                ->where('approval_level', '>', $chain->approval_level)
                ->where('status', 'pendiente')
                ->first();

            // Si no hay más niveles, la ausencia se aprueba finalmente
            if (!$nextPending) {
                $this->finalizeApproval($chain->absence, $approver);
            }

            return $chain;
        });
    }

    public function reject(
        AbsenceApprovalChain $chain,
        User $rejector,
        string $reason
    ): AbsenceApprovalChain {
        return DB::transaction(function () use ($chain, $rejector, $reason) {
            $absence = $chain->absence;

            // Marcar esta cadena como rechazada
            $chain->update([
                'status' => 'rechazado',
                'assigned_to' => $rejector->id,
                'notes' => $reason,
                'completed_at' => now(),
            ]);

            // Rechazar TODAS las cadenas pendientes
            $absence->approvalChains()
                ->where('status', 'pendiente')
                ->update([
                    'status' => 'rechazado',
                    'completed_at' => now(),
                ]);

            // Actualizar estado de ausencia
            $absence->update([
                'status' => AbsenceStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'approved_by' => $rejector->id,
                'approved_at' => now(),
            ]);

            return $chain->fresh();
        });
    }

    protected function finalizeApproval(Absence $absence, User $finalApprover): void
    {
        $absence->update([
            'status' => AbsenceStatus::APPROVED->value,
            'approved_by' => $finalApprover->id,
            'approved_at' => now(),
        ]);
    }
}
