<?php

namespace Database\Seeders;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use Carbon\Carbon;
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

        $currentYear = now()->year;
        $currentMonth = now()->month;

        // ============================================
        // AUSENCIAS PARA COLABORADORES
        // ============================================
        $absencesData = [
            // Ana Sofía López - Vacaciones aprobadas y pendientes
            [
                'user' => $colaboradores->firstWhere('email', 'ana.lopez@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, 1, 15),
                'end' => Carbon::createFromDate($currentYear, 1, 22),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 6,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'ana.lopez@ausentra.com'),
                'type' => $permiso,
                'start' => Carbon::createFromDate($currentYear, 3, 10),
                'end' => Carbon::createFromDate($currentYear, 3, 10),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'ana.lopez@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, $currentMonth + 1, 10),
                'end' => Carbon::createFromDate($currentYear, $currentMonth + 1, 18),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => true,
                'include_sunday' => true,
                'include_holidays' => false,
                'total_days' => 7,
            ],

            // Luis Fernando Ramírez - Vacaciones usadas
            [
                'user' => $colaboradores->firstWhere('email', 'luis.ramirez@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, 2, 20),
                'end' => Carbon::createFromDate($currentYear, 2, 28),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 7,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'luis.ramirez@ausentra.com'),
                'type' => $enfermedad,
                'start' => Carbon::createFromDate($currentYear, 4, 1),
                'end' => Carbon::createFromDate($currentYear, 4, 3),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 3,
            ],

            // Camila Torres - Permisos varios
            [
                'user' => $colaboradores->firstWhere('email', 'camila.torres@ausentra.com'),
                'type' => $permiso,
                'start' => Carbon::createFromDate($currentYear, 1, 25),
                'end' => Carbon::createFromDate($currentYear, 1, 25),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'camila.torres@ausentra.com'),
                'type' => $diaLibre,
                'start' => Carbon::createFromDate($currentYear, 3, 20),
                'end' => Carbon::createFromDate($currentYear, 3, 22),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => true,
                'include_sunday' => true,
                'include_holidays' => false,
                'total_days' => 2,
            ],

            // Daniel Sánchez - Vacaciones y rechazo
            [
                'user' => $colaboradores->firstWhere('email', 'daniel.sanchez@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, 1, 5),
                'end' => Carbon::createFromDate($currentYear, 1, 12),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 6,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'daniel.sanchez@ausentra.com'),
                'type' => $permiso,
                'start' => Carbon::createFromDate($currentYear, 4, 15),
                'end' => Carbon::createFromDate($currentYear, 4, 16),
                'status' => AbsenceStatus::REJECTED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 1,
                'reject_reason' => 'Falta de personal en el área',
            ],

            // Valentina Díaz - Cumpleaños y permisos
            [
                'user' => $colaboradores->firstWhere('email', 'valentina.diaz@ausentra.com'),
                'type' => $cumpleanos,
                'start' => Carbon::createFromDate($currentYear, 4, 22),
                'end' => Carbon::createFromDate($currentYear, 4, 22),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 1,
            ],

            // Sebastián Vargas -多种 absences
            [
                'user' => $colaboradores->firstWhere('email', 'sebastian.vargas@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, 2, 10),
                'end' => Carbon::createFromDate($currentYear, 2, 17),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 6,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'sebastian.vargas@ausentra.com'),
                'type' => $enfermedad,
                'start' => Carbon::createFromDate($currentYear, 4, 5),
                'end' => Carbon::createFromDate($currentYear, 4, 6),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 2,
            ],

            // Isabella Castro - Pendiente
            [
                'user' => $colaboradores->firstWhere('email', 'isabella.castro@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, $currentMonth + 2, 5),
                'end' => Carbon::createFromDate($currentYear, $currentMonth + 2, 12),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => true,
                'include_sunday' => true,
                'include_holidays' => false,
                'total_days' => 6,
            ],

            // Mateo Hernández - Aprobadas recientes
            [
                'user' => $colaboradores->firstWhere('email', 'mateo.hernandez@ausentra.com'),
                'type' => $permiso,
                'start' => Carbon::createFromDate($currentYear, 3, 28),
                'end' => Carbon::createFromDate($currentYear, 3, 28),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'mateo.hernandez@ausentra.com'),
                'type' => $diaLibre,
                'start' => Carbon::createFromDate($currentYear, 4, 18),
                'end' => Carbon::createFromDate($currentYear, 4, 19),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => true,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 1.5,
            ],

            // Sofia Natalia Jiménez - Vacaciones
            [
                'user' => $colaboradores->firstWhere('email', 'sofia.jimenez@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, 1, 20),
                'end' => Carbon::createFromDate($currentYear, 1, 31),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 10,
            ],
            [
                'user' => $colaboradores->firstWhere('email', 'sofia.jimenez@ausentra.com'),
                'type' => $permiso,
                'start' => Carbon::createFromDate($currentYear, 4, 2),
                'end' => Carbon::createFromDate($currentYear, 4, 2),
                'status' => AbsenceStatus::APPROVED,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 0.5,
            ],

            // Gabriel Peña - Ausencia próxima
            [
                'user' => $colaboradores->firstWhere('email', 'gabriel.pena@ausentra.com'),
                'type' => $vacaciones,
                'start' => Carbon::createFromDate($currentYear, $currentMonth + 1, 1),
                'end' => Carbon::createFromDate($currentYear, $currentMonth + 1, 8),
                'status' => AbsenceStatus::PENDING,
                'include_saturday' => true,
                'include_sunday' => true,
                'include_holidays' => false,
                'total_days' => 6,
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
        // AUSENCIA PARA ADMIN (1)
        // ============================================
        $admin = $admins->first();
        if ($admin) {
            Absence::create([
                'user_id' => $admin->id,
                'absence_type_id' => $vacaciones->id,
                'start_datetime' => Carbon::createFromDate($currentYear, 3, 15),
                'end_datetime' => Carbon::createFromDate($currentYear, 3, 20),
                'status' => AbsenceStatus::APPROVED->value,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'total_days' => 4,
                'notes' => 'Vacaciones',
            ]);
        }

        // ============================================
        // AUSENCIA PENDIENTE PARA OTRO ADMIN
        // ============================================
        $admin2 = $admins->skip(1)->first();
        if ($admin2) {
            Absence::create([
                'user_id' => $admin2->id,
                'absence_type_id' => $permiso->id,
                'start_datetime' => Carbon::createFromDate($currentYear, $currentMonth + 1, 5),
                'end_datetime' => Carbon::createFromDate($currentYear, $currentMonth + 1, 5),
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
