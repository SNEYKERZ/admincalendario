<?php

namespace Database\Seeders;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class AbsenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Sembrando ausencias...');

        // Obtener tipos de ausencia
        $vacaciones = AbsenceType::where('name', 'Vacaciones')->first();
        $diaLibre = AbsenceType::where('name', 'Día libre')->first();
        $permiso = AbsenceType::where('name', 'Permiso')->first();
        $enfermedad = AbsenceType::where('name', 'Enfermedad')->first();
        $cumpleanos = AbsenceType::where('name', 'Cumpleaños')->first();

        // Obtener colaboradores (excluir superadmin y admins)
        $colaboradores = User::where('role', 'colaborador')->get();
        $admins = User::where('role', 'admin')->get();

        // Ventana relativa a "hoy": mes pasado, mes actual y mes siguiente,
        // para que el calendario nunca arranque con datos vacíos o vencidos.
        $lastMonth = now()->subMonthNoOverflow();
        $thisMonth = now();
        $nextMonth = now()->addMonthNoOverflow();

        $date = fn (CarbonInterface $month, int $day) => Carbon::createFromDate($month->year, $month->month, $day);

        // ============================================
        // AUSENCIAS PARA COLABORADORES
        // ============================================
        $absencesData = [
            // --- Mes pasado: solicitudes ya resueltas (aprobadas/rechazadas) ---
            [
                'user' => $colaboradores->firstWhere('email', 'ana.lopez@ausentra.com'),
                'type' => $vacaciones,
                'start' => $date($lastMonth, 3),
                'end' => $date($lastMonth, 10),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 6,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'luis.ramirez@ausentra.com'),
                'type' => $enfermedad,
                'start' => $date($lastMonth, 12),
                'end' => $date($lastMonth, 14),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 3,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'camila.torres@ausentra.com'),
                'type' => $permiso,
                'start' => $date($lastMonth, 20),
                'end' => $date($lastMonth, 20),
                'status' => AbsenceStatus::REJECTED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
                'reject_reason' => 'Falta de personal en el área',
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'daniel.sanchez@ausentra.com'),
                'type' => $vacaciones,
                'start' => $date($lastMonth, 24),
                'end' => $date($lastMonth, 28),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 5,
            ],

            // --- Mes actual: mezcla de aprobadas, pendientes y rechazadas ---
            [
                'user' => $colaboradores->firstWhere('email', 'valentina.diaz@ausentra.com'),
                'type' => $cumpleanos,
                'start' => $date($thisMonth, 22),
                'end' => $date($thisMonth, 22),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 1,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'sebastian.vargas@ausentra.com'),
                'type' => $vacaciones,
                'start' => $date($thisMonth, 8),
                'end' => $date($thisMonth, 15),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 6,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'isabella.castro@ausentra.com'),
                'type' => $permiso,
                'start' => $date($thisMonth, 18),
                'end' => $date($thisMonth, 18),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'mateo.hernandez@ausentra.com'),
                'type' => $diaLibre,
                'start' => $date($thisMonth, 25),
                'end' => $date($thisMonth, 26),
                'status' => AbsenceStatus::REJECTED,
                'include_saturday' => true,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 1.5,
                'reject_reason' => 'Alta carga de trabajo en el área',
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'sofia.jimenez@ausentra.com'),
                'type' => $enfermedad,
                'start' => $date($thisMonth, 2),
                'end' => $date($thisMonth, 3),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 2,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'gabriel.pena@ausentra.com'),
                'type' => $permiso,
                'start' => $date($thisMonth, 29),
                'end' => $date($thisMonth, 29),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],

            // --- Mes siguiente: solicitudes anticipadas, mayoría pendientes ---
            [
                'user' => $colaboradores->firstWhere('email', 'ana.lopez@ausentra.com'),
                'type' => $vacaciones,
                'start' => $date($nextMonth, 12),
                'end' => $date($nextMonth, 19),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => true,
                'include_sunday' => true,
                'include_holidays' => false,
                'total_days' => 8,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'luis.ramirez@ausentra.com'),
                'type' => $vacaciones,
                'start' => $date($nextMonth, 5),
                'end' => $date($nextMonth, 9),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 5,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'daniel.sanchez@ausentra.com'),
                'type' => $permiso,
                'start' => $date($nextMonth, 21),
                'end' => $date($nextMonth, 21),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'camila.torres@ausentra.com'),
                'type' => $diaLibre,
                'start' => $date($nextMonth, 15),
                'end' => $date($nextMonth, 16),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 2,
            ],
        ];

        foreach ($absencesData as $data) {
            if (! $data['user'] || ! $data['type']) {
                continue;
            }

            $absenceData = [
                'user_id' => $data['user']->id,
                'absence_type_id' => $data['type']->id,
                'start_datetime' => $data['start'],
                'end_datetime' => $data['end'],
                'status' => $data['status']->value,
                'include_saturday' => $data['include_saturday'],
                'include_sunday' => $data['include_sunday'],
                'include_holidays' => $data['include_holidays'],
                'total_days' => $data['total_days'],
                'notes' => $data['type']->name,
            ];

            if (isset($data['reject_reason'])) {
                $absenceData['notes'] = $data['reject_reason'];
            }

            Absence::create($absenceData);
        }

        // ============================================
        // AUSENCIAS PARA ADMINS (mes actual y mes siguiente)
        // ============================================
        $admin = $admins->first();
        if ($admin) {
            Absence::create([
                'user_id' => $admin->id,
                'absence_type_id' => $vacaciones->id,
                'start_datetime' => $date($thisMonth, 10),
                'end_datetime' => $date($thisMonth, 15),
                'status' => AbsenceStatus::APPROVED->value,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 4,
                'notes' => 'Vacaciones',
            ]);

            // El admin también necesita al menos una solicitud pendiente propia
            // (antes solo tenía la aprobada de arriba, por eso siempre veía "todo aprobado").
            Absence::create([
                'user_id' => $admin->id,
                'absence_type_id' => $permiso->id,
                'start_datetime' => $date($nextMonth, 3),
                'end_datetime' => $date($nextMonth, 3),
                'status' => AbsenceStatus::PENDING->value,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
                'notes' => 'Trámite personal',
            ]);
        }

        $admin2 = $admins->skip(1)->first();
        if ($admin2) {
            Absence::create([
                'user_id' => $admin2->id,
                'absence_type_id' => $permiso->id,
                'start_datetime' => $date($nextMonth, 7),
                'end_datetime' => $date($nextMonth, 7),
                'status' => AbsenceStatus::PENDING->value,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
                'notes' => 'Cita médica',
            ]);
        }

        $this->command->info('✅ Ausencias sembradas correctamente');
    }
}
