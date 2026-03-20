<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\User;
use App\Models\AbsenceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AbsenceService
{
    public function __construct(
        protected VacationService $vacationService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */
    public function create(array $data): Absence
    {
        return DB::transaction(function () use ($data) {

            // 👇 ADMIN puede elegir usuario
            $user = auth()->user()->isAdmin()
                ? User::findOrFail($data['user_id'])
                : auth()->user();

            $type = AbsenceType::findOrFail($data['absence_type_id']);

            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);

            // 🧠 calcular días automáticamente
            $totalDays = $start->diffInDays($end);

            if ($totalDays <= 0) {
                throw ValidationException::withMessages([
                    'dates' => 'Rango de fechas inválido'
                ]);
            }

            /*
        |----------------------------------------------------------
        | SOLAPAMIENTO
        |----------------------------------------------------------
        */
            $overlap = Absence::where('user_id', $user->id)
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_datetime', '<', $end)
                        ->where('end_datetime', '>', $start);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'date' => 'Ya existe una ausencia en ese rango'
                ]);
            }

            /*
        |----------------------------------------------------------
        | VALIDAR VACACIONES
        |----------------------------------------------------------
        */
            if ($type->deducts_vacation) {

                $available = $user->vacationYears()
                    ->where('expires_at', '>=', now())
                    ->get()
                    ->sum(fn($y) => $y->allocated_days - $y->used_days);

                if ($available < $totalDays) {
                    throw ValidationException::withMessages([
                        'days' => 'No tiene saldo disponible'
                    ]);
                }
            }

            /*
        |----------------------------------------------------------
        | STATUS
        |----------------------------------------------------------
        */
            $status = auth()->user()->isAdmin()
                ? 'aprobado'
                : 'pendiente';

            /*
        |----------------------------------------------------------
        | CREAR
        |----------------------------------------------------------
        */
            $absence = Absence::create([
                ...$data,
                'user_id' => $user->id,
                'total_days' => $totalDays,
                'status' => $status,
            ]);

            /*
        |----------------------------------------------------------
        | DESCONTAR
        |----------------------------------------------------------
        */
            if ($status === 'aprobado' && $type->deducts_vacation) {
                $this->vacationService->deductDays(
                    $user,
                    $totalDays
                );
            }

            return $absence;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ( DRAG & DROP / EDICIÓN)
    |--------------------------------------------------------------------------
    */
    public function update(Absence $absence, array $data): Absence
    {
        return DB::transaction(function () use ($absence, $data) {

            $user = $absence->user;
            $type = $absence->type;

            $start = Carbon::parse($data['start_datetime'])->startOfDay();
            $end   = Carbon::parse($data['end_datetime'])->endOfDay();

            $totalDays = $start->diffInDays($end) + 1;

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SOLAPAMIENTO (excluyendo actual)
            |--------------------------------------------------------------------------
            */
            $overlap = Absence::where('user_id', $user->id)
                ->where('id', '!=', $absence->id)
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_datetime', '<', $end)
                        ->where('end_datetime', '>', $start);
                })
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'date' => 'Conflicto con otra ausencia'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SALDO SI YA ESTABA APROBADO
            |--------------------------------------------------------------------------
            */
            if ($absence->status === 'aprobado' && $type->deducts_vacation) {

                $available = $user->vacationYears()
                    ->where('expires_at', '>=', now())
                    ->get()
                    ->sum(fn($y) => $y->allocated_days - $y->used_days);

                if ($available < $totalDays) {
                    throw ValidationException::withMessages([
                        'days' => 'No tiene saldo disponible'
                    ]);
                }
            }

            $absence->update([
                ...$data,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'total_days' => $totalDays,
            ]);

            return $absence;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | APROBAR
    |--------------------------------------------------------------------------
    */
    public function approve(Absence $absence, User $admin): Absence
    {
        return DB::transaction(function () use ($absence, $admin) {

            if ($absence->status === 'aprobado') {
                return $absence;
            }

            if ($absence->type->deducts_vacation) {

                $available = $absence->user->vacationYears()
                    ->where('expires_at', '>=', now())
                    ->get()
                    ->sum(fn($y) => $y->allocated_days - $y->used_days);

                if ($available < $absence->total_days) {
                    throw ValidationException::withMessages([
                        'days' => 'No tiene saldo disponible'
                    ]);
                }

                $this->vacationService->deductDays(
                    $absence->user,
                    $absence->total_days
                );
            }

            $absence->update([
                'status' => 'aprobado',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            return $absence;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RECHAZAR
    |--------------------------------------------------------------------------
    */
    public function reject(Absence $absence, User $admin): Absence
    {
        if ($absence->status === 'aprobado') {
            throw ValidationException::withMessages([
                'status' => 'No se puede rechazar una aprobada'
            ]);
        }

        $absence->update([
            'status' => 'rechazado',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        return $absence;
    }
}
