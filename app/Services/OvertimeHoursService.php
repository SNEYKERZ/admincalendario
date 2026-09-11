<?php

namespace App\Services;

use App\Models\CompanySettings;
use App\Models\OvertimeHours;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeHoursService
{
    public function create(array $data): OvertimeHours
    {
        return DB::transaction(function () use ($data) {
            return $this->createOne($data, auth()->user());
        });
    }

    public function createBatch(array $records): array
    {
        return DB::transaction(function () use ($records) {
            $authUser = auth()->user();
            $created = [];
            $errors = [];

            foreach ($records as $index => $record) {
                try {
                    $created[] = $this->createOne($record, $authUser);
                } catch (ValidationException $e) {
                    $errors[$index] = $e->errors;
                    throw $e;
                }
            }

            return $created;
        });
    }

    private function createOne(array $data, User $authUser): OvertimeHours
    {
        if ($authUser->isAdmin() && isset($data['user_id'])) {
            $user = User::findOrFail($data['user_id']);
            if ($user->tenant_id !== $authUser->tenant_id) {
                throw ValidationException::withMessages([
                    'user_id' => 'Solo puedes registrar horas extras de usuarios del mismo tenant.',
                ]);
            }
        } else {
            $user = $authUser;
        }

            $date = Carbon::parse($data['date']);
            $startTime = Carbon::createFromFormat('H:i', $data['start_time']);
            $endTime = Carbon::createFromFormat('H:i', $data['end_time']);

            // Validar que end_time > start_time
            if ($endTime->lte($startTime)) {
                throw ValidationException::withMessages([
                    'end_time' => 'La hora de fin debe ser posterior a la hora de inicio.',
                ]);
            }

            // Calcular automáticamente las horas
            $calculatedHours = $endTime->floatDiffInHours($startTime);

            // Extraer componentes de fecha
            $dayOfWeek = $date->translatedFormat('l'); // Día de la semana en español
            $dayOfMonth = $date->day;
            $month = $date->translatedFormat('F'); // Nombre del mes en español

            // Validaciones
            $exceedsDailyLimit = $calculatedHours > 2;
            $exceedsWeeklyLimit = $this->checkWeeklyLimit($user, $date, $calculatedHours);
            $respectsSchedule = $this->checkCompanySchedule($user, $startTime, $endTime);

            // Si hay validaciones que fallan, lanzar excepciones
            if ($exceedsDailyLimit) {
                throw ValidationException::withMessages([
                    'hours' => 'El límite máximo de horas extra diarias es 2 horas (Ley colombiana).',
                ]);
            }

            if ($exceedsWeeklyLimit) {
                throw ValidationException::withMessages([
                    'hours' => 'El total de horas extra semanales no puede exceder 12 horas (Ley colombiana).',
                ]);
            }

            if (!$respectsSchedule) {
                throw ValidationException::withMessages([
                    'start_time' => 'Las horas extra deben registrarse después de la jornada laboral.',
                ]);
            }

            // Verificar que no exista otro registro para la misma fecha
            $existing = OvertimeHours::where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->where('date', $date)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'date' => 'Ya existe un registro de horas extra para esta fecha.',
                ]);
            }

        return OvertimeHours::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'day_of_month' => $dayOfMonth,
            'month' => $month,
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $endTime->format('H:i:s'),
            'client' => $data['client'] ?? null,
            'project' => $data['project'] ?? null,
            'reason' => $data['reason'],
            'hours' => $calculatedHours,
            'status' => 'pending',
            'exceeds_daily_limit' => false,
            'exceeds_weekly_limit' => false,
            'respects_company_schedule' => $respectsSchedule,
        ]);
    }

    public function update(OvertimeHours $overtime, array $data): OvertimeHours
    {
        return DB::transaction(function () use ($overtime, $data) {
            if ($overtime->tenant_id !== auth()->user()->tenant_id) {
                throw ValidationException::withMessages([
                    'unauthorized' => 'No tienes permiso para actualizar este registro.',
                ]);
            }

            $date = Carbon::parse($data['date']);
            $startTime = Carbon::createFromFormat('H:i', $data['start_time']);
            $endTime = Carbon::createFromFormat('H:i', $data['end_time']);

            if ($endTime->lte($startTime)) {
                throw ValidationException::withMessages([
                    'end_time' => 'La hora de fin debe ser posterior a la hora de inicio.',
                ]);
            }

            $calculatedHours = $endTime->floatDiffInHours($startTime);
            $dayOfWeek = $date->translatedFormat('l');
            $dayOfMonth = $date->day;
            $month = $date->translatedFormat('F');

            $exceedsDailyLimit = $calculatedHours > 2;
            $exceedsWeeklyLimit = $this->checkWeeklyLimit($overtime->user, $date, $calculatedHours, $overtime->id);
            $respectsSchedule = $this->checkCompanySchedule($overtime->user, $startTime, $endTime);

            if ($exceedsDailyLimit) {
                throw ValidationException::withMessages([
                    'hours' => 'El límite máximo de horas extra diarias es 2 horas (Ley colombiana).',
                ]);
            }

            if ($exceedsWeeklyLimit) {
                throw ValidationException::withMessages([
                    'hours' => 'El total de horas extra semanales no puede exceder 12 horas (Ley colombiana).',
                ]);
            }

            if (!$respectsSchedule) {
                throw ValidationException::withMessages([
                    'start_time' => 'Las horas extra deben registrarse después de la jornada laboral.',
                ]);
            }

            $existing = OvertimeHours::where('tenant_id', $overtime->tenant_id)
                ->where('user_id', $overtime->user_id)
                ->where('date', $date)
                ->where('id', '!=', $overtime->id)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'date' => 'Ya existe otro registro de horas extra para esta fecha.',
                ]);
            }

            $overtime->update([
                'date' => $date,
                'day_of_week' => $dayOfWeek,
                'day_of_month' => $dayOfMonth,
                'month' => $month,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'client' => $data['client'] ?? null,
                'project' => $data['project'] ?? null,
                'reason' => $data['reason'],
                'hours' => $calculatedHours,
                'respects_company_schedule' => $respectsSchedule,
            ]);

            return $overtime->fresh();
        });
    }

    public function approve(OvertimeHours $overtime, User $approver): OvertimeHours
    {
        return DB::transaction(function () use ($overtime, $approver) {
            $overtime->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $overtime->fresh();
        });
    }

    public function reject(OvertimeHours $overtime, User $rejector, string $reason = ''): OvertimeHours
    {
        return DB::transaction(function () use ($overtime, $rejector, $reason) {
            $overtime->update([
                'status' => 'rejected',
                'approved_by' => $rejector->id,
                'approval_notes' => $reason,
                'approved_at' => now(),
            ]);

            return $overtime->fresh();
        });
    }

    protected function checkWeeklyLimit(User $user, Carbon $date, float $hours, ?int $excludeId = null): bool
    {
        $weekStart = $date->copy()->startOfWeek();
        $weekEnd = $date->copy()->endOfWeek();

        $query = OvertimeHours::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->where('status', 'approved');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingWeekly = $query->sum('hours');

        return ($existingWeekly + $hours) > 12;
    }

    protected function checkCompanySchedule(User $user, Carbon $startTime, Carbon $endTime): bool
    {
        $settings = CompanySettings::where('tenant_id', $user->tenant_id)->first();

        if (!$settings) {
            return true; // Si no hay configuración, permitir
        }

        $workdayEnd = Carbon::createFromFormat('H:i:s', $settings->workday_end);
        $overtimeStart = Carbon::createFromFormat('H:i:s', $settings->overtime_start ?? '18:00:00');

        // Las horas extra deben comenzar después de la jornada laboral (workday_end)
        return $startTime->gte($workdayEnd) || $startTime->gte($overtimeStart);
    }
}
