<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use App\Models\VacationYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    protected ?User $adminUser = null;

    public function run(): void
    {
        $this->command->info('🌱 Iniciando seeders de demostración...');

        // 1. Tipos de ausencia
        $this->seedAbsenceTypes();

        // 2. Usuarios (2 admin + 13 colaboradores)
        $this->seedUsers();

        // 3. Vacaciones por año
        $this->seedVacations();

        // 4. Ausencias (marzo-mayo 2026)
        $this->seedAbsences();

        $this->command->info('✅ Seeders completados exitosamente!');
        $this->command->info('📋 Credenciales de prueba:');
        $this->command->info('   Admin: admin@admin.com - password = identificación');
        $this->command->info('   Colaboradores: usuario1@demo.com, usuario2@demo.com, etc. - password = identificación');
    }

    protected function seedAbsenceTypes(): void
    {
        AbsenceType::upsert([
            [
                'name' => 'Vacaciones',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => false,
                'default_include_saturday' => false,
                'default_include_sunday' => false,
                'default_include_holidays' => false,
                'color' => '#3498db',
            ],
            [
                'name' => 'Día libre',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#2ecc71',
            ],
            [
                'name' => 'Permiso',
                'deducts_vacation' => false,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#f39c12',
            ],
            [
                'name' => 'Cumpleaños',
                'deducts_vacation' => false,
                'requires_approval' => false,
                'counts_as_hours' => false,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#9b59b6',
            ],
            [
                'name' => 'Enfermedad',
                'deducts_vacation' => false,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#e74c3c',
            ],
        ], 'name');
    }

    protected function seedUsers(): void
    {
        $users = [
            // Administradores
            ['first_name' => 'Carlos', 'last_name' => 'Rodríguez', 'identification' => '10000001', 'role' => 'admin', 'hire_date' => '2020-01-15'],
            ['first_name' => 'María', 'last_name' => 'Gómez', 'identification' => '10000002', 'role' => 'admin', 'hire_date' => '2021-03-20'],

            // Colaboradores
            ['first_name' => 'Juan', 'last_name' => 'Pérez', 'identification' => '10000003', 'role' => 'colaborador', 'hire_date' => '2022-06-01'],
            ['first_name' => 'Ana', 'last_name' => 'López', 'identification' => '10000004', 'role' => 'colaborador', 'hire_date' => '2022-08-15'],
            ['first_name' => 'Pedro', 'last_name' => 'Martínez', 'identification' => '10000005', 'role' => 'colaborador', 'hire_date' => '2023-01-10'],
            ['first_name' => 'Laura', 'last_name' => 'Fernández', 'identification' => '10000006', 'role' => 'colaborador', 'hire_date' => '2023-04-05'],
            ['first_name' => 'Miguel', 'last_name' => 'Torres', 'identification' => '10000007', 'role' => 'colaborador', 'hire_date' => '2023-07-20'],
            ['first_name' => 'Sofía', 'last_name' => 'Ramírez', 'identification' => '10000008', 'role' => 'colaborador', 'hire_date' => '2023-09-01'],
            ['first_name' => 'Diego', 'last_name' => 'Herrera', 'identification' => '10000009', 'role' => 'colaborador', 'hire_date' => '2024-01-08'],
            ['first_name' => 'Carmen', 'last_name' => 'Morales', 'identification' => '10000010', 'role' => 'colaborador', 'hire_date' => '2024-02-15'],
            ['first_name' => 'Roberto', 'last_name' => 'García', 'identification' => '10000011', 'role' => 'colaborador', 'hire_date' => '2024-04-01'],
            ['first_name' => 'Isabel', 'last_name' => 'Castro', 'identification' => '10000012', 'role' => 'colaborador', 'hire_date' => '2024-05-10'],
            ['first_name' => 'Andrés', 'last_name' => 'Vega', 'identification' => '10000013', 'role' => 'colaborador', 'hire_date' => '2024-06-15'],
            ['first_name' => 'Patricia', 'last_name' => 'Mendoza', 'identification' => '10000014', 'role' => 'colaborador', 'hire_date' => '2024-08-01'],
            ['first_name' => 'Fernando', 'last_name' => 'Escobar', 'identification' => '10000015', 'role' => 'colaborador', 'hire_date' => '2024-10-20'],
        ];

        $adminUsers = [];

        foreach ($users as $userData) {
            $firstName = $userData['first_name'];
            $lastName = $userData['last_name'];
            $fullName = $firstName.' '.$lastName;

            $email = strtolower($firstName).'.'.strtolower($lastName).'@demo.com';
            if ($userData['role'] === 'admin') {
                $email = strtolower($firstName[0]).strtolower($lastName).'@admin.com';
            }

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'identification' => $userData['identification'],
                    'phone' => '+57'.rand(3000000000, 3999999999),
                    'password' => Hash::make($userData['identification']),
                    'role' => $userData['role'],
                    'hire_date' => $userData['hire_date'],
                    'birth_date' => Carbon::now()->subYears(rand(22, 55))->subDays(rand(0, 365))->toDateString(),
                ]
            );

            if ($userData['role'] === 'admin') {
                $adminUsers[] = $user;
            }
        }

        // Guardar el primer admin para aprobar ausencias
        $this->adminUser = $adminUsers[0] ?? User::where('role', 'admin')->first();
    }

    protected function seedVacations(): void
    {
        $users = User::where('role', 'colaborador')->get();
        $currentYear = 2026;
        $previousYear = 2025;

        foreach ($users as $user) {
            // Año anterior (puede tener días vencidos o no)
            VacationYear::updateOrCreate(
                ['user_id' => $user->id, 'year' => $previousYear],
                [
                    'allocated_days' => 15,
                    'used_days' => rand(10, 15),
                    'expires_at' => Carbon::createFromDate($previousYear + 1, 12, 31),
                ]
            );

            // Año actual
            $allocated = 15;
            // Algunos usuarios ya han usado algunos días
            $used = rand(0, 8);

            VacationYear::updateOrCreate(
                ['user_id' => $user->id, 'year' => $currentYear],
                [
                    'allocated_days' => $allocated,
                    'used_days' => $used,
                    'expires_at' => Carbon::createFromDate($currentYear + 1, 12, 31),
                ]
            );
        }
    }

    protected function seedAbsences(): void
    {
        $users = User::where('role', 'colaborador')->get();
        $types = AbsenceType::all();
        $adminId = $this->adminUser?->id ?? User::where('role', 'admin')->first()?->id;

        // Fechas de marzo a mayo 2026
        $marchStart = Carbon::create(2026, 3, 1);
        $marchEnd = Carbon::create(2026, 3, 31);
        $aprilStart = Carbon::create(2026, 4, 1);
        $aprilEnd = Carbon::create(2026, 4, 30);
        $mayStart = Carbon::create(2026, 5, 1);
        $mayEnd = Carbon::create(2026, 5, 31);

        $absencesData = [];

        // === MARZO 2026 ===

        // Usuario 1 - Vacaciones aprobadas (10 días)
        $absencesData[] = $this->createAbsenceData($users[0], $types[0], '2026-03-09', '2026-03-20', true, 8);

        // Usuario 2 - Permiso aprobado (1 día)
        $absencesData[] = $this->createAbsenceData($users[1], $types[2], '2026-03-15', '2026-03-15', true, 0.5);

        // Usuario 3 - Día libre aprobado (2 días)
        $absencesData[] = $this->createAbsenceData($users[2], $types[1], '2026-03-23', '2026-03-24', true, 2);

        // Usuario 4 - Pendiente (vacaciones)
        $absencesData[] = $this->createAbsenceData($users[3], $types[0], '2026-03-25', '2026-03-28', false, 0);

        // Usuario 5 - Rechazado (permiso)
        $absencesData[] = $this->createAbsenceData($users[4], $types[2], '2026-03-10', '2026-03-10', false, 0, 'rejected');

        // Usuario 6 - Cumpleaños aprobado
        $absencesData[] = $this->createAbsenceData($users[5], $types[3], '2026-03-18', '2026-03-18', true, 1);

        // === ABRIL 2026 ===

        // Usuario 1 - Permiso aprobado
        $absencesData[] = $this->createAbsenceData($users[0], $types[2], '2026-04-07', '2026-04-07', true, 0.5);

        // Usuario 2 - Vacaciones (pendiente)
        $absencesData[] = $this->createAbsenceData($users[1], $types[0], '2026-04-21', '2026-04-30', false, 0);

        // Usuario 3 - Día libre aprobado
        $absencesData[] = $this->createAbsenceData($users[2], $types[1], '2026-04-14', '2026-04-14', true, 1);

        // Usuario 4 - Vacaciones aprobadas
        $absencesData[] = $this->createAbsenceData($users[3], $types[0], '2026-04-07', '2026-04-11', true, 5);

        // Usuario 5 - Enfermedad aprobada
        $absencesData[] = $this->createAbsenceData($users[4], $types[4], '2026-04-22', '2026-04-23', true, 2);

        // Usuario 7 - Pendiente
        $absencesData[] = $this->createAbsenceData($users[6], $types[2], '2026-04-28', '2026-04-28', false, 0);

        // Usuario 8 - Rechazado
        $absencesData[] = $this->createAbsenceData($users[7], $types[0], '2026-04-15', '2026-04-18', false, 0, 'rejected');

        // === MAYO 2026 ===

        // Usuario 1 - Vacaciones aprobadas
        $absencesData[] = $this->createAbsenceData($users[0], $types[0], '2026-05-12', '2026-05-16', true, 5);

        // Usuario 2 - Permiso aprobado
        $absencesData[] = $this->createAbsenceData($users[1], $types[2], '2026-05-05', '2026-05-05', true, 0.5);

        // Usuario 3 - Pendiente
        $absencesData[] = $this->createAbsenceData($users[2], $types[0], '2026-05-26', '2026-05-30', false, 0);

        // Usuario 4 - Día libre aprobado
        $absencesData[] = $this->createAbsenceData($users[3], $types[1], '2026-05-19', '2026-05-19', true, 1);

        // Usuario 5 - Vacaciones aprobadas
        $absencesData[] = $this->createAbsenceData($users[4], $types[0], '2026-05-19', '2026-05-23', true, 5);

        // Usuario 6 - Pendiente (permiso)
        $absencesData[] = $this->createAbsenceData($users[5], $types[2], '2026-05-08', '2026-05-08', false, 0);

        // Usuario 7 - Cumpleaños aprobado
        $absencesData[] = $this->createAbsenceData($users[6], $types[3], '2026-05-20', '2026-05-20', true, 1);

        // Usuario 8 - Rechazado
        $absencesData[] = $this->createAbsenceData($users[7], $types[1], '2026-05-12', '2026-05-13', false, 0, 'rejected');

        // Usuario 9 - Aprobado
        $absencesData[] = $this->createAbsenceData($users[8], $types[2], '2026-05-22', '2026-05-22', true, 0.5);

        // Usuario 10 - Pendiente
        $absencesData[] = $this->createAbsenceData($users[9], $types[0], '2026-05-28', '2026-05-30', false, 0);

        // Crear las ausencias
        foreach ($absencesData as $data) {
            $type = $types->firstWhere('id', $data['absence_type_id']);

            $status = $data['status'];
            $approvedBy = null;
            $approvedAt = null;

            if ($status === 'approved' || $status === 'aprobado') {
                $approvedBy = $adminId;
                $approvedAt = now();
                $status = 'aprobado';
            } elseif ($status === 'rejected' || $status === 'rechazado') {
                $status = 'rechazado';
            } else {
                $status = 'pendiente';
            }

            // Calcular días incluyendo/excluyendo fines de semana
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);
            $totalDays = $this->calculateDays($start, $end, $type);

            Absence::create([
                'user_id' => $data['user_id'],
                'absence_type_id' => $data['absence_type_id'],
                'start_datetime' => $start,
                'end_datetime' => $end,
                'include_saturday' => $type->default_include_saturday,
                'include_sunday' => $type->default_include_sunday,
                'include_holidays' => $type->default_include_holidays,
                'holiday_country' => 'CO',
                'total_days' => $totalDays,
                'total_hours' => $totalDays * 8,
                'status' => $status,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'notes' => 'Demo data - '.($status === 'approved' ? 'approved' : ($status === 'rejected' ? 'rejected' : 'pending')),
            ]);

            // Si está aprobado y deduce vacaciones, actualizar used_days
            if ($status === 'aprobado' && $type->deducts_vacation) {
                $user = User::find($data['user_id']);
                $vacationYear = VacationYear::where('user_id', $user->id)->where('year', 2026)->first();
                if ($vacationYear) {
                    $vacationYear->used_days += $totalDays;
                    $vacationYear->save();
                }
            }
        }

        // Agregar algunas ausencias más para variedad
        $extraAbsences = [
            ['user_id' => $users[10]->id, 'type_id' => $types[2]->id, 'start' => '2026-04-15', 'end' => '2026-04-15', 'status' => 'aprobado'],
            ['user_id' => $users[11]->id, 'type_id' => $types[1]->id, 'start' => '2026-05-07', 'end' => '2026-05-08', 'status' => 'aprobado'],
            ['user_id' => $users[12]->id, 'type_id' => $types[4]->id, 'start' => '2026-03-25', 'end' => '2026-03-26', 'status' => 'aprobado'],
            ['user_id' => $users[13]->id, 'type_id' => $types[0]->id, 'start' => '2026-05-05', 'end' => '2026-05-09', 'status' => 'pendiente'],
            ['user_id' => $users[14]->id, 'type_id' => $types[2]->id, 'start' => '2026-04-10', 'end' => '2026-04-10', 'status' => 'aprobado'],
        ];

        foreach ($extraAbsences as $extra) {
            $type = $types->firstWhere('id', $extra['type_id']);
            $start = Carbon::parse($extra['start']);
            $end = Carbon::parse($extra['end']);
            $totalDays = $this->calculateDays($start, $end, $type);

            $approvedBy = $extra['status'] === 'aprobado' ? $adminId : null;
            $approvedAt = $extra['status'] === 'aprobado' ? now() : null;

            Absence::create([
                'user_id' => $extra['user_id'],
                'absence_type_id' => $extra['type_id'],
                'start_datetime' => $start,
                'end_datetime' => $end,
                'include_saturday' => $type->default_include_saturday,
                'include_sunday' => $type->default_include_sunday,
                'include_holidays' => $type->default_include_holidays,
                'holiday_country' => 'CO',
                'total_days' => $totalDays,
                'total_hours' => $totalDays * 8,
                'status' => $extra['status'],
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
            ]);

            if ($extra['status'] === 'aprobado' && $type->deducts_vacation) {
                $user = User::find($extra['user_id']);
                $vacationYear = VacationYear::where('user_id', $user->id)->where('year', 2026)->first();
                if ($vacationYear) {
                    $vacationYear->used_days += $totalDays;
                    $vacationYear->save();
                }
            }
        }
    }

    protected function createAbsenceData($user, $type, $start, $end, $isApproved, $usedDays, $statusOverride = null): array
    {
        return [
            'user_id' => $user->id,
            'absence_type_id' => $type->id,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'is_approved' => $isApproved,
            'status' => $statusOverride ?? ($isApproved ? 'approved' : 'pending'),
        ];
    }

    protected function calculateDays(Carbon $start, Carbon $end, $type): float
    {
        if ($type->default_include_saturday && $type->default_include_sunday) {
            return (float) $start->diffInDays($end) + 1;
        }

        // Excluir fines de semana
        $days = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return (float) $days;
    }
}
