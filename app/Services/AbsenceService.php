<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\User;
use App\Models\AbsenceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsenceService
{
    public function __construct(
        protected VacationService $vacationService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CREAR AUSENCIA / SOLICITUD
    |--------------------------------------------------------------------------
    */
    public function create(array $data): Absence
    {
        return DB::transaction(function () use ($data) {

            $user = auth()->user();
            $type = AbsenceType::findOrFail($data['absence_type_id']);

            /*
            |--------------------------------------------------------------------------
            | DEFINIR ESTADO
            |--------------------------------------------------------------------------
            */

            // Admin → crea aprobado directamente
            // Colaborador → crea pendiente
            $status = $user->isAdmin()
                ? 'aprobado'
                : 'pendiente';

            /*
            |--------------------------------------------------------------------------
            | VALIDACIONES ESPECIALES (NEGOCIO)
            |--------------------------------------------------------------------------
            */

            $start = \Carbon\Carbon::parse($data['start_datetime']);

            // 🎂 Cumpleaños
            if ($type->name === 'Cumpleaños') {

                if ($start->month !== $user->birth_date->month) {
                    throw ValidationException::withMessages([
                        'start_datetime' => 'Solo puede tomarse en el mes de cumpleaños'
                    ]);
                }

                $alreadyTaken = Absence::where('user_id', $user->id)
                    ->whereYear('start_datetime', $start->year)
                    ->where('absence_type_id', $type->id)
                    ->exists();

                if ($alreadyTaken) {
                    throw ValidationException::withMessages([
                        'start_datetime' => 'Ya tomó su día de cumpleaños este año'
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CREAR REGISTRO
            |--------------------------------------------------------------------------
            */

            $absence = Absence::create([
                ...$data,
                'user_id' => $user->id,
                'status' => $status,
            ]);

            /*
            |--------------------------------------------------------------------------
            | SI ES ADMIN Y ES VACACIONES → DESCONTAR DE UNA VEZ
            |--------------------------------------------------------------------------
            */

            if (
                $status === 'aprobado' &&
                $type->deducts_vacation
            ) {
                $this->vacationService->deductDays(
                    $absence->user,
                    $absence->total_days
                );
            }

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

            // evitar doble aprobación
            if ($absence->status === 'aprobado') {
                return $absence;
            }

            /*
            |--------------------------------------------------------------------------
            | DESCONTAR VACACIONES SI APLICA
            |--------------------------------------------------------------------------
            */

            if ($absence->type->deducts_vacation) {
                $this->vacationService->deductDays(
                    $absence->user,
                    $absence->total_days
                );
            }

            /*
            |--------------------------------------------------------------------------
            | ACTUALIZAR
            |--------------------------------------------------------------------------
            */

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
        // opcional: evitar rechazar algo ya aprobado
        if ($absence->status === 'aprobado') {
            throw ValidationException::withMessages([
                'status' => 'No se puede rechazar una ausencia ya aprobada'
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