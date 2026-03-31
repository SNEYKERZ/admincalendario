<?php

namespace App\Services;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use App\Notifications\AbsenceCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AbsenceService
{
    public function __construct(
        protected VacationService $vacationService,
        protected AbsenceCalculationService $absenceCalculationService
    ) {}

    public function create(array $data): Absence
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user()->isAdmin()
                ? User::findOrFail($data['user_id'])
                : auth()->user();

            $type = AbsenceType::findOrFail($data['absence_type_id']);
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
        Notification::send($admins, $notification);
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
                $absence->user->notify(new AbsenceApproved($absence));
            } elseif ($status === AbsenceStatus::REJECTED) {
                $absence->user->notify(new AbsenceRejected($absence));
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
}
