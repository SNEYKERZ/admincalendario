<?php

namespace App\Services;

use App\Enums\AbsenceStatus;
use App\Managers\TenantManager;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use App\Notifications\AbsenceApproved;
use App\Notifications\AbsenceCreated;
use App\Notifications\AbsenceRejected;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AbsenceService
{
    public function __construct(
        protected VacationService $vacationService,
        protected AbsenceCalculationService $absenceCalculationService,
        protected TenantManager $tenantManager
    ) {}

    public function create(array $data): Absence
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user()->isAdmin()
                ? User::findOrFail($data['user_id'])
                : auth()->user();

            $type = $this->resolveAbsenceType($data['absence_type_id']);

            if (! $type) {
                throw ValidationException::withMessages([
                    'absence_type_id' => 'El tipo de ausencia no está disponible para este tenant.',
                ]);
            }
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);

            $calculation = $this->absenceCalculationService->calculate(
                $type,
                $start,
                $end,
                $this->absenceCalculationService->resolveOptions($type, $data)
            );

            if ($calculation['total_days'] <= 0) {
                throw ValidationException::withMessages([
                    'dates' => 'Rango de fechas invalido',
                ]);
            }

            $overlap = Absence::where('user_id', $user->id)
                ->whereIn('status', [AbsenceStatus::PENDING->value, AbsenceStatus::APPROVED->value])
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_datetime', '<', $end)
                        ->where('end_datetime', '>', $start);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'date' => 'Ya existe una ausencia en ese rango',
                ]);
            }

            if ($type->deducts_vacation) {
                $this->ensureVacationBalance($user, $calculation['total_days']);
            }

            $isApproved = auth()->user()->isAdmin();
            $status = $isApproved ? AbsenceStatus::APPROVED : AbsenceStatus::PENDING;

            $absence = Absence::create([
                ...$data,
                'user_id' => $user->id,
                'include_saturday' => $calculation['include_saturday'],
                'include_sunday' => $calculation['include_sunday'],
                'include_holidays' => $calculation['include_holidays'],
                'holiday_country' => $calculation['holiday_country'],
                'total_days' => $calculation['total_days'],
                'total_hours' => $calculation['total_hours'],
                'status' => $status->value,
                'approved_by' => $isApproved ? auth()->id() : null,
                'approved_at' => $isApproved ? now() : null,
            ]);

            if ($isApproved && $type->deducts_vacation) {
                $this->vacationService->deductDays($user, $calculation['total_days']);
            }

            // Notificar a administradores si es ausencia pendiente
            if ($status === AbsenceStatus::PENDING) {
                $this->sendNotificationToAdmins(new AbsenceCreated($absence));
            }

            return $absence->fresh(['user', 'type', 'approver']);
        });
    }

    protected function sendNotificationToAdmins($notification): void
    {
        $admins = User::admins()->get();

        if ($admins->isEmpty()) {
            return;
        }

        try {
            Notification::send($admins, $notification);
        } catch (QueryException $exception) {
            if ($this->isMissingNotificationsTableException($exception)) {
                Log::warning('Tabla notifications no existe. Se omite envío de notificaciones.', [
                    'error' => $exception->getMessage(),
                ]);

                return;
            }

            throw $exception;
        }
    }

    protected function notifyUserSafely(User $user, object $notification): void
    {
        try {
            $user->notify($notification);
        } catch (QueryException $exception) {
            if ($this->isMissingNotificationsTableException($exception)) {
                Log::warning('Tabla notifications no existe. Se omite notificación de usuario.', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                return;
            }

            throw $exception;
        }
    }

    protected function isMissingNotificationsTableException(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'notifications')
            && str_contains(strtolower($exception->getMessage()), 'base table or view not found');
    }

    public function update(Absence $absence, array $data): Absence
    {
        return DB::transaction(function () use ($absence, $data) {
            $absence->loadMissing(['user', 'type']);

            $user = $absence->user;
            $type = $absence->type;
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);

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

            if ($calculation['total_days'] <= 0) {
                throw ValidationException::withMessages([
                    'dates' => 'Rango de fechas invalido',
                ]);
            }

            $overlap = Absence::where('user_id', $user->id)
                ->where('id', '!=', $absence->id)
                ->whereIn('status', [AbsenceStatus::PENDING->value, AbsenceStatus::APPROVED->value])
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_datetime', '<', $end)
                        ->where('end_datetime', '>', $start);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'date' => 'Conflicto con otra ausencia',
                ]);
            }

            if ($type->deducts_vacation && $absence->isApproved()) {
                $available = $user->availableVacationDays() + $absence->total_days;

                if ($available < $calculation['total_days']) {
                    throw ValidationException::withMessages([
                        'days' => 'No tiene saldo disponible',
                    ]);
                }

                $delta = round($calculation['total_days'] - $absence->total_days, 2);

                if ($delta > 0) {
                    $this->vacationService->deductDays($user, $delta);
                } elseif ($delta < 0) {
                    $this->vacationService->restoreDays($user, abs($delta));
                }
            }

            $absence->update([
                ...$data,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'include_saturday' => $calculation['include_saturday'],
                'include_sunday' => $calculation['include_sunday'],
                'include_holidays' => $calculation['include_holidays'],
                'holiday_country' => $calculation['holiday_country'],
                'total_days' => $calculation['total_days'],
                'total_hours' => $calculation['total_hours'],
            ]);

            return $absence->fresh(['user', 'type', 'approver']);
        });
    }

    public function approve(Absence $absence, User $admin): Absence
    {
        return $this->setStatus($absence, AbsenceStatus::APPROVED, $admin);
    }

    public function reject(Absence $absence, User $admin): Absence
    {
        return $this->setStatus($absence, AbsenceStatus::REJECTED, $admin);
    }

    public function pending(Absence $absence, User $admin): Absence
    {
        return $this->setStatus($absence, AbsenceStatus::PENDING, $admin);
    }

    protected function setStatus(Absence $absence, AbsenceStatus $status, User $admin): Absence
    {
        return DB::transaction(function () use ($absence, $status, $admin) {
            $absence->loadMissing(['user', 'type']);

            if ($absence->type->deducts_vacation) {
                if ($absence->isApproved() && $status !== AbsenceStatus::APPROVED) {
                    $this->vacationService->restoreDays($absence->user, $absence->total_days);
                }

                if (! $absence->isApproved() && $status === AbsenceStatus::APPROVED) {
                    $this->ensureVacationBalance($absence->user, $absence->total_days);
                    $this->vacationService->deductDays($absence->user, $absence->total_days);
                }
            }

            $absence->update([
                'status' => $status->value,
                'approved_by' => $status === AbsenceStatus::PENDING ? null : $admin->id,
                'approved_at' => $status === AbsenceStatus::PENDING ? null : now(),
            ]);

            // Notificar al usuario sobre el cambio de estado
            $absence->load('user');
            if ($status === AbsenceStatus::APPROVED) {
                $this->notifyUserSafely($absence->user, new AbsenceApproved($absence));
            } elseif ($status === AbsenceStatus::REJECTED) {
                $this->notifyUserSafely($absence->user, new AbsenceRejected($absence));
            }

            return $absence->fresh(['user', 'type', 'approver']);
        });
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
