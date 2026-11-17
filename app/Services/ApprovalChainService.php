<?php

namespace App\Services;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalChainService
{
    public function __construct(
        protected ?VacationService $vacationService = null
    ) {
        $this->vacationService ??= app(VacationService::class);
    }

    public function createApprovalChain(Absence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $absence->loadMissing(['user.area', 'type']);
            $user = $absence->user;

            // Nivel 1: Jefe de Área (nunca el propio solicitante).
            if ($user->area
                && $user->area->hasManager()
                && (int) $user->area->area_manager_id !== (int) $user->id) {
                AbsenceApprovalChain::create([
                    'absence_id' => $absence->id,
                    'approval_level' => AbsenceApprovalChain::LEVEL_AREA_MANAGER,
                    'assigned_to' => $user->area->area_manager_id,
                    'status' => 'pendiente',
                ]);
            }

            // Nivel 2: Admin del tenant del solicitante.
            if ($absence->type?->requires_approval) {
                $admin = $this->resolveAdminApprover($user);

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

    /**
     * Rehace la cadena (p.ej. cuando un admin devuelve la ausencia a pendiente).
     */
    public function resetApprovalChain(Absence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $absence->approvalChains()->delete();
            $this->createApprovalChain($absence);
        });
    }

    public function approve(
        AbsenceApprovalChain $chain,
        User $approver,
        ?string $notes = null
    ): AbsenceApprovalChain {
        return DB::transaction(function () use ($chain, $approver, $notes) {
            $chain = AbsenceApprovalChain::query()->lockForUpdate()->findOrFail($chain->id);

            if (! $chain->isPending()) {
                throw ValidationException::withMessages([
                    'status' => 'Este nivel de aprobación ya fue resuelto.',
                ]);
            }

            $chain->update([
                'status' => 'aprobado',
                'assigned_to' => $approver->id,
                'notes' => $notes,
                'completed_at' => now(),
            ]);

            $chain->refresh();

            // Verificar si quedan niveles pendientes (de cualquier nivel)
            $nextPending = $chain->absence->approvalChains()
                ->where('status', 'pendiente')
                ->exists();

            // Si no hay más niveles, la ausencia se aprueba finalmente
            if (! $nextPending) {
                $this->finalizeApproval($chain->absence, $approver);
            }

            return $chain;
        });
    }

    /**
     * Aprobación directa por un administrador: resuelve todos los niveles
     * pendientes y aprueba la ausencia.
     */
    public function approveAll(Absence $absence, User $approver, ?string $notes = null): void
    {
        DB::transaction(function () use ($absence, $approver, $notes) {
            $absence->approvalChains()
                ->where('status', 'pendiente')
                ->update([
                    'status' => 'aprobado',
                    'assigned_to' => $approver->id,
                    'notes' => $notes,
                    'completed_at' => now(),
                ]);

            $this->finalizeApproval($absence, $approver);
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
                'rejected_at' => now(),
            ]);

            return $chain->fresh();
        });
    }

    /**
     * Aprobación final: descuenta saldo de vacaciones si el tipo lo exige.
     * Antes la ausencia pasaba a "aprobado" sin descontar días.
     */
    protected function finalizeApproval(Absence $absence, User $finalApprover): void
    {
        $absence = Absence::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($absence->id);
        $absence->loadMissing(['user', 'type']);

        if ($absence->isApproved()) {
            return;
        }

        if ($absence->type?->deducts_vacation) {
            if ($absence->user->availableVacationDays() < $absence->total_days) {
                throw ValidationException::withMessages([
                    'days' => 'El colaborador no tiene saldo de vacaciones suficiente para aprobar esta ausencia.',
                ]);
            }

            $this->vacationService->deductDays($absence->user, (float) $absence->total_days);
        }

        $absence->update([
            'status' => AbsenceStatus::APPROVED->value,
            'approved_by' => $finalApprover->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    protected function resolveAdminApprover(User $requester): ?User
    {
        $base = User::query()
            ->withoutGlobalScopes()
            ->where(fn ($query) => $query->whereNull('is_active')->orWhere('is_active', true))
            ->when(
                $requester->tenant_id,
                fn ($query) => $query->where('tenant_id', $requester->tenant_id),
                fn ($query) => $query->whereNull('tenant_id')
            );

        // Preferir un admin distinto del solicitante; superadmins solo como último recurso.
        return (clone $base)->regularAdmins()->where('id', '!=', $requester->id)->orderBy('id')->first()
            ?? (clone $base)->admins()->where('id', '!=', $requester->id)->orderBy('id')->first()
            ?? (clone $base)->admins()->orderBy('id')->first();
    }
}
