<?php

namespace App\Services;

use App\Enums\AbsenceStatus;
use App\Managers\TenantManager;
use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\AbsenceType;
use App\Models\User;
use App\Notifications\AbsenceApproved;
use App\Notifications\AbsencePendingApproval;
use App\Notifications\AbsenceRejected;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class AbsenceService
{
    /**
     * Rango máximo (en días calendario) de una ausencia. Evita bucles enormes
     * en el cálculo de días hábiles y cubre la licencia más larga (maternidad,
     * 126 días hábiles).
     */
    public const MAX_RANGE_DAYS = 366;

    public function __construct(
        protected VacationService $vacationService,
        protected AbsenceCalculationService $absenceCalculationService,
        protected ApprovalChainService $approvalChainService,
        protected AuditService $auditService,
        protected TenantManager $tenantManager
    ) {}

    public function create(array $data): Absence
    {
        $absence = DB::transaction(function () use ($data) {
            $authUser = auth()->user();
            $user = $authUser->isAdmin() && ! empty($data['user_id'])
                ? User::findOrFail($data['user_id'])
                : $authUser;

            $type = $this->resolveAbsenceType($data['absence_type_id'] ?? null);

            if (! $type) {
                throw ValidationException::withMessages([
                    'absence_type_id' => 'El tipo de ausencia no está disponible para este tenant.',
                ]);
            }

            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);

            $this->ensureValidRange($start, $end);

            $calculation = $this->absenceCalculationService->calculate(
                $type,
                $start,
                $end,
                $this->absenceCalculationService->resolveOptions($type, $data)
            );

            $this->ensureWithinTypeLimits($type, $calculation);

            if ($this->hasOverlap($user, $type, $start, $end)) {
                throw ValidationException::withMessages([
                    'date' => 'Ya existe una ausencia en ese rango',
                ]);
            }

            if ($type->deducts_vacation) {
                $this->ensureVacationBalance($user, $calculation['total_days']);
            }

            // Las ausencias SIEMPRE nacen pendientes (sin auto-aprobación de admins).
            $absence = Absence::create([
                'tenant_id' => $user->tenant_id ?? $this->tenantManager->getTenantId(),
                'user_id' => $user->id,
                'absence_type_id' => $type->id,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'notes' => $data['notes'] ?? $data['reason'] ?? null,
                'include_saturday' => $calculation['include_saturday'],
                'include_sunday' => $calculation['include_sunday'],
                'include_holidays' => $calculation['include_holidays'],
                'holiday_country' => $calculation['holiday_country'],
                'total_days' => $calculation['total_days'],
                'total_hours' => $calculation['total_hours'],
                'status' => AbsenceStatus::PENDING->value,
            ]);

            $this->approvalChainService->createApprovalChain($absence);

            $this->auditService->logAction(
                $absence,
                'created',
                ['total_days' => $absence->total_days],
                null
            );

            return $absence;
        });

        // Notificar al primer aprobador (fuera de la transacción: un fallo de
        // correo no debe deshacer la solicitud).
        $firstChain = $absence->approvalChains()
            ->where('status', 'pendiente')
            ->orderBy('approval_level')
            ->with('assignedTo')
            ->first();

        if ($firstChain?->assignedTo) {
            $this->notifySafely($firstChain->assignedTo, new AbsencePendingApproval($absence, $firstChain));
        }

        return $absence->fresh(['user', 'type', 'approvalChains']);
    }

    /**
     * Edición de una ausencia PENDIENTE. Se revalidan rango, solapamiento,
     * límites del tipo y saldo de vacaciones.
     */
    public function update(Absence $absence, array $data): Absence
    {
        return DB::transaction(function () use ($absence, $data) {
            $absence = Absence::query()->lockForUpdate()->findOrFail($absence->id);
            $absence->loadMissing(['user', 'type']);

            if (! $absence->isPending()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden editar ausencias pendientes.',
                ]);
            }

            $user = $absence->user;
            $type = $absence->type;
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);

            $this->ensureValidRange($start, $end);

            $calculation = $this->absenceCalculationService->calculate(
                $type,
                $start,
                $end,
                $this->absenceCalculationService->resolveOptions($type, [
                    'include_saturday' => $data['include_saturday'] ?? $absence->include_saturday,
                    'include_sunday' => $data['include_sunday'] ?? $absence->include_sunday,
                    'include_holidays' => $data['include_holidays'] ?? $absence->include_holidays,
                    'holiday_country' => $data['holiday_country'] ?? $absence->holiday_country,
                ])
            );

            $this->ensureWithinTypeLimits($type, $calculation);

            if ($this->hasOverlap($user, $type, $start, $end, $absence->id)) {
                throw ValidationException::withMessages([
                    'date' => 'Conflicto con otra ausencia',
                ]);
            }

            if ($type->deducts_vacation) {
                $this->ensureVacationBalance($user, $calculation['total_days']);
            }

            $before = $absence->only(['start_datetime', 'end_datetime', 'total_days']);

            $absence->update([
                'start_datetime' => $start,
                'end_datetime' => $end,
                'notes' => array_key_exists('reason', $data) ? $data['reason'] : $absence->notes,
                'include_saturday' => $calculation['include_saturday'],
                'include_sunday' => $calculation['include_sunday'],
                'include_holidays' => $calculation['include_holidays'],
                'holiday_country' => $calculation['holiday_country'],
                'total_days' => $calculation['total_days'],
                'total_hours' => $calculation['total_hours'],
            ]);

            $this->auditService->logAction($absence, 'updated', [
                'before' => $before,
                'after' => $absence->only(['start_datetime', 'end_datetime', 'total_days']),
            ]);

            return $absence->fresh(['user', 'type', 'approver']);
        });
    }

    /**
     * Aprueba una ausencia pendiente.
     *
     * - Con $chain (flujo de ApprovalController): resuelve ese nivel; la
     *   ausencia queda aprobada cuando no quedan niveles pendientes.
     * - Sin $chain (admin desde /absences/{id}/approve): aprobación final,
     *   resuelve todos los niveles pendientes.
     *
     * El descuento de vacaciones se hace en la aprobación final.
     */
    public function approve(Absence $absence, User $approver, ?AbsenceApprovalChain $chain = null, ?string $notes = null): Absence
    {
        $absence = DB::transaction(function () use ($absence, $approver, $chain, $notes) {
            $absence = Absence::query()->lockForUpdate()->findOrFail($absence->id);

            if (! $absence->isPending()) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden aprobar ausencias pendientes.',
                ]);
            }

            if ($chain) {
                if ((int) $chain->absence_id !== (int) $absence->id) {
                    abort(404);
                }

                $this->approvalChainService->approve($chain, $approver, $notes);
            } else {
                $this->approvalChainService->approveAll($absence, $approver, $notes);
            }

            $absence->refresh();

            $this->auditService->logAction(
                $absence,
                $absence->isApproved() ? 'approved' : 'approval_level_completed',
                ['status' => $absence->status->value],
                $notes
            );

            return $absence;
        });

        if ($absence->isApproved()) {
            $this->notifySafely($absence->user, new AbsenceApproved($absence));
        }

        return $absence->fresh(['user', 'type', 'approver']);
    }

    /**
     * Rechaza una ausencia pendiente o revoca una aprobada (restaurando el
     * saldo de vacaciones descontado).
     */
    public function reject(Absence $absence, User $rejector, string $reason = '', ?AbsenceApprovalChain $chain = null): Absence
    {
        $absence = DB::transaction(function () use ($absence, $rejector, $reason, $chain) {
            $absence = Absence::query()->lockForUpdate()->findOrFail($absence->id);
            $absence->loadMissing(['user', 'type']);

            if ($absence->isRejected()) {
                throw ValidationException::withMessages([
                    'status' => 'La ausencia ya está rechazada.',
                ]);
            }

            $wasApproved = $absence->isApproved();

            if ($wasApproved && $absence->type?->deducts_vacation) {
                $this->vacationService->restoreDays($absence->user, (float) $absence->total_days);
            }

            $pendingChain = $chain ?? $absence->getApprovalChainStatus();

            if ($pendingChain && $pendingChain->isPending()) {
                $this->approvalChainService->reject($pendingChain, $rejector, $reason);
            } else {
                $absence->approvalChains()
                    ->where('status', 'pendiente')
                    ->update(['status' => 'rechazado', 'completed_at' => now()]);

                $absence->update([
                    'status' => AbsenceStatus::REJECTED->value,
                    'rejection_reason' => $reason !== '' ? $reason : null,
                    'approved_by' => $rejector->id,
                    'approved_at' => now(),
                    'rejected_at' => now(),
                ]);
            }

            $absence->refresh();

            $this->auditService->logAction(
                $absence,
                'rejected',
                ['status' => AbsenceStatus::REJECTED->value, 'was_approved' => $wasApproved],
                $reason !== '' ? $reason : null
            );

            return $absence;
        });

        $this->notifySafely($absence->user, new AbsenceRejected($absence, $reason));

        return $absence->fresh(['user', 'type', 'approver']);
    }

    /**
     * Devuelve una ausencia a "pendiente": restaura saldo si estaba aprobada y
     * rehace la cadena de aprobación (antes la cadena quedaba resuelta y la
     * ausencia ya no podía volver a aprobarse por el flujo de cadenas).
     */
    public function pending(Absence $absence, User $admin): Absence
    {
        return DB::transaction(function () use ($absence, $admin) {
            $absence = Absence::query()->lockForUpdate()->findOrFail($absence->id);
            $absence->loadMissing(['user', 'type']);

            if ($absence->isPending()) {
                return $absence->fresh(['user', 'type', 'approver']);
            }

            if ($absence->isApproved() && $absence->type?->deducts_vacation) {
                $this->vacationService->restoreDays($absence->user, (float) $absence->total_days);
            }

            $absence->update([
                'status' => AbsenceStatus::PENDING->value,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $this->approvalChainService->resetApprovalChain($absence);

            $this->auditService->logAction($absence, 'reverted_to_pending', [
                'status' => AbsenceStatus::PENDING->value,
                'by' => $admin->id,
            ]);

            return $absence->fresh(['user', 'type', 'approver']);
        });
    }

    /**
     * Elimina una ausencia restaurando el saldo si estaba aprobada.
     */
    public function delete(Absence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $absence = Absence::query()->lockForUpdate()->findOrFail($absence->id);
            $absence->loadMissing(['user', 'type']);

            if ($absence->isApproved() && $absence->type?->deducts_vacation) {
                $this->vacationService->restoreDays($absence->user, (float) $absence->total_days);
            }

            $absence->approvalChains()->delete();
            $absence->delete();
        });
    }

    /**
     * Solapamiento unificado (antes el FormRequest y el servicio usaban
     * criterios distintos).
     *
     * - Tipos por horas: solapamiento estricto de intervalos (permite 8-10 y 10-12).
     * - Tipos por días: se comparan días calendario completos.
     */
    public function hasOverlap(User $user, AbsenceType $type, Carbon $start, Carbon $end, ?int $ignoreId = null): bool
    {
        if ($this->absenceCalculationService->isHourlyType($type)) {
            $rangeStart = $start;
            $rangeEnd = $end;
            $strict = true;
        } else {
            $rangeStart = $start->copy()->startOfDay();
            $rangeEnd = $end->copy()->endOfDay();
            $strict = false;
        }

        return Absence::query()
            ->where('user_id', $user->id)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereIn('status', [AbsenceStatus::PENDING->value, AbsenceStatus::APPROVED->value])
            ->where('start_datetime', $strict ? '<' : '<=', $rangeEnd)
            ->where('end_datetime', $strict ? '>' : '>=', $rangeStart)
            ->exists();
    }

    protected function ensureValidRange(Carbon $start, Carbon $end): void
    {
        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'end_datetime' => 'La fecha/hora final debe ser mayor a la inicial',
            ]);
        }

        if ($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_datetime' => 'El rango de la ausencia no puede superar '.self::MAX_RANGE_DAYS.' días.',
            ]);
        }
    }

    protected function ensureWithinTypeLimits(AbsenceType $type, array $calculation): void
    {
        if ($calculation['total_days'] <= 0) {
            throw ValidationException::withMessages([
                'dates' => 'Rango de fechas invalido',
            ]);
        }

        if ($type->max_days_limit && $calculation['total_days'] > $type->max_days_limit) {
            throw ValidationException::withMessages([
                'dates' => "El límite máximo permitido para {$type->name} es {$type->max_days_limit} días según la ley colombiana.",
            ]);
        }
    }

    protected function notifySafely(?User $user, object $notification): void
    {
        if (! $user) {
            return;
        }

        try {
            Notification::send($user, $notification);
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar la notificación de ausencia.', [
                'user_id' => $user->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function ensureVacationBalance(User $user, float $days): void
    {
        if ($user->availableVacationDays() < $days) {
            throw ValidationException::withMessages([
                'days' => 'No tiene saldo disponible',
            ]);
        }
    }

    protected function resolveAbsenceType(int|string|null $absenceTypeId): ?AbsenceType
    {
        if (! $absenceTypeId) {
            return null;
        }

        $tenantId = $this->tenantManager->getTenantId();

        return AbsenceType::withoutGlobalScopes()
            ->where('id', (int) $absenceTypeId)
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->where(function ($innerQuery) use ($tenantId) {
                    $innerQuery
                        ->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            }, function ($query) {
                $query->whereNull('tenant_id');
            })
            ->first();
    }
}
