<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use App\Models\VacationYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener áreas
        $rrhh = Area::where('name', 'Recursos Humanos')->first();
        $tecnologia = Area::where('name', 'Tecnología')->first();
        $ventas = Area::where('name', 'Ventas')->first();
        $marketing = Area::where('name', 'Marketing')->first();
        $finanzas = Area::where('name', 'Finanzas')->first();
        $operaciones = Area::where('name', 'Operaciones')->first();
        $servicioCliente = Area::where('name', 'Servicio al Cliente')->first();
        $adminArea = Area::where('name', 'Administración')->first();

        // ============================================
        // 1 SUPERADMIN
        // ============================================
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@ausentra.com'],
            [
                'name' => 'Carlos Andrés Rodríguez',
                'first_name' => 'Carlos Andrés',
                'last_name' => 'Rodríguez',
                'identification' => '80000100',
                'phone' => '+57 310 100 0001',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPERADMIN->value,
                'is_active' => true,
                'birth_date' => '1980-05-15',
                'hire_date' => '2020-01-01',
            ]
        );

        // ============================================
        // 2 ADMINISTRADORES
        // ============================================
        $admin1 = User::updateOrCreate(
            ['email' => 'admin@ausentra.com'],
            [
                'name' => 'María Elena González',
                'first_name' => 'María Elena',
                'last_name' => 'González',
                'identification' => '80000101',
                'phone' => '+57 310 100 0002',
                'email' => 'admin@ausentra.com',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN->value,
                'is_active' => true,
                'birth_date' => '1985-08-22',
                'hire_date' => '2021-03-15',
                'area_id' => $rrhh?->id,
            ]
        );

        $admin2 = User::updateOrCreate(
            ['email' => 'jefe.operaciones@ausentra.com'],
            [
                'name' => 'José Luis Martínez',
                'first_name' => 'José Luis',
                'last_name' => 'Martínez',
                'identification' => '80000102',
                'phone' => '+57 310 100 0003',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN->value,
                'is_active' => true,
                'birth_date' => '1982-11-30',
                'hire_date' => '2020-06-01',
                'area_id' => $operaciones?->id,
            ]
        );

        // ============================================
        // 10 COLABORADORES
        // ============================================
        $colaboradores = [
            [
                'name' => 'Ana Sofía López',
                'first_name' => 'Ana Sofía',
                'last_name' => 'López',
                'identification' => '10001001',
                'phone' => '+57 310 200 0001',
                'email' => 'ana.lopez@ausentra.com',
                'birth_date' => '1995-03-12',
                'hire_date' => '2023-01-10',
                'area' => $rrhh,
                'vacation_days' => 12,
            ],
            [
                'name' => 'Luis Fernando Ramírez',
                'first_name' => 'Luis Fernando',
                'last_name' => 'Ramírez',
                'identification' => '10001002',
                'phone' => '+57 310 200 0002',
                'email' => 'luis.ramirez@ausentra.com',
                'birth_date' => '1990-07-25',
                'hire_date' => '2022-08-20',
                'area' => $tecnologia,
                'vacation_days' => 15,
            ],
            [
                'name' => 'Camila Isabella Torres',
                'first_name' => 'Camila Isabella',
                'last_name' => 'Torres',
                'identification' => '10001003',
                'phone' => '+57 310 200 0003',
                'email' => 'camila.torres@ausentra.com',
                'birth_date' => '1998-12-05',
                'hire_date' => '2023-04-15',
                'area' => $ventas,
                'vacation_days' => 10,
            ],
            [
                'name' => 'Daniel Alejandro Sánchez',
                'first_name' => 'Daniel Alejandro',
                'last_name' => 'Sánchez',
                'identification' => '10001004',
                'phone' => '+57 310 200 0004',
                'email' => 'daniel.sanchez@ausentra.com',
                'birth_date' => '1988-09-18',
                'hire_date' => '2021-11-01',
                'area' => $tecnologia,
                'vacation_days' => 18,
            ],
            [
                'name' => 'Valentina Díaz Moreno',
                'first_name' => 'Valentina',
                'last_name' => 'Díaz Moreno',
                'identification' => '10001005',
                'phone' => '+57 310 200 0005',
                'email' => 'valentina.diaz@ausentra.com',
                'birth_date' => '1996-04-22',
                'hire_date' => '2022-05-30',
                'area' => $marketing,
                'vacation_days' => 14,
            ],
            [
                'name' => 'Sebastián Vargas López',
                'first_name' => 'Sebastián',
                'last_name' => 'Vargas López',
                'identification' => '10001006',
                'phone' => '+57 310 200 0006',
                'email' => 'sebastian.vargas@ausentra.com',
                'birth_date' => '1992-01-08',
                'hire_date' => '2021-02-15',
                'area' => $finanzas,
                'vacation_days' => 16,
            ],
            [
                'name' => 'Isabella María Castro',
                'first_name' => 'Isabella María',
                'last_name' => 'Castro',
                'identification' => '10001007',
                'phone' => '+57 310 200 0007',
                'email' => 'isabella.castro@ausentra.com',
                'birth_date' => '1997-06-30',
                'hire_date' => '2023-07-01',
                'area' => $servicioCliente,
                'vacation_days' => 8,
            ],
            [
                'name' => 'Mateo Hernández Ruiz',
                'first_name' => 'Mateo',
                'last_name' => 'Hernández Ruiz',
                'identification' => '10001008',
                'phone' => '+57 310 200 0008',
                'email' => 'mateo.hernandez@ausentra.com',
                'birth_date' => '1994-10-14',
                'hire_date' => '2022-09-10',
                'area' => $ventas,
                'vacation_days' => 13,
            ],
            [
                'name' => 'Sofia Natalia Jiménez',
                'first_name' => 'Sofia Natalia',
                'last_name' => 'Jiménez',
                'identification' => '10001009',
                'phone' => '+57 310 200 0009',
                'email' => 'sofia.jimenez@ausentra.com',
                'birth_date' => '1993-02-28',
                'hire_date' => '2021-06-20',
                'area' => $operaciones,
                'vacation_days' => 17,
            ],
            [
                'name' => 'Gabriel Eduardo Peña',
                'first_name' => 'Gabriel Eduardo',
                'last_name' => 'Peña',
                'identification' => '10001010',
                'phone' => '+57 310 200 0010',
                'email' => 'gabriel.pena@ausentra.com',
                'birth_date' => '1991-08-11',
                'hire_date' => '2022-03-05',
                'area' => $tecnologia,
                'vacation_days' => 15,
            ],
        ];

        $year = now()->year;

        foreach ($colaboradores as $colab) {
            $area = $colab['area'];
            unset($colab['area'], $colab['vacation_days']);

            $user = User::updateOrCreate(
                ['email' => $colab['email']],
                array_merge($colab, [
                    'password' => Hash::make('password'),
                    'role' => UserRole::COLLABORATOR->value,
                    'is_active' => true,
                    'area_id' => $area?->id,
                ])
            );

            // Crear VacationYear para cada colaborador
            VacationYear::updateOrCreate(
                ['user_id' => $user->id, 'year' => $year],
                [
                    'allocated_days' => $colab['vacation_days'] ?? 15,
                    'used_days' => 0,
                    'expires_at' => now()->endOfYear(),
                ]
            );

            // También crear año anterior con días usados
            VacationYear::updateOrCreate(
                ['user_id' => $user->id, 'year' => $year - 1],
                [
                    'allocated_days' => $colab['vacation_days'] ?? 15,
                    'used_days' => rand(5, 15),
                    'expires_at' => now()->subYear()->endOfYear(),
                ]
            );
        }

        // ============================================
        // RESUMEN
        // ============================================
        $this->command->info('✅ Usuarios sembrados correctamente:');
        $this->command->info('   - 1 Superadmin: superadmin@ausentra.com / password');
        $this->command->info('   - 2 Administradores: admin@ausentra.com / password');
        $this->command->info('   - 10 Colaboradores: [nombre]@ausentra.com / password');
    }
}
