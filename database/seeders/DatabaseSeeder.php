<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Orden de ejecución:
     * 0. Tenant principal (requerido por el resto de seeders)
     * 1. Datos base (ausence types, settings)
     * 2. Configuración (company, áreas, planes)
     * 3. Usuarios (superadmin, admins, colaboradores)
     * 4. Datos operativos (ausencias)
     */
    public function run(): void
    {
        $this->call([
            // 0. Tenant principal (AbsenceTypeSeeder, RoleSeeder, etc. dependen de él)
            TenantSeeder::class,

            // 1. Datos base
            AbsenceTypeSeeder::class,
            SubscriptionSettingsSeeder::class,
            RoleSeeder::class, // Roles del sistema

            // 2. Configuración
            SubscriptionPlanSeeder::class,
            ModuleSeeder::class, // Módulos y su asociación con los planes
            CompanySettingsSeeder::class,
            AreasSeeder::class,

            // 3. Usuarios (crear después de áreas)
            UserSeeder::class,

            // 4. Ausencias (crear después de usuarios y tipos)
            AbsenceSeeder::class,

            // 5. Demo data para cadena de aprobación
            ApprovalChainDemoSeeder::class,

            // 6. Horas extra (para reportes de horas extra)
            OvertimeHoursSeeder::class,
        ]);
    }
}
