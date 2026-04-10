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
     * 1. Datos base (ausence types, settings)
     * 2. Configuración (company, áreas, planes)
     * 3. Usuarios (superadmin, admins, colaboradores)
     * 4. Datos operativos (ausencias)
     */
    public function run(): void
    {
        $this->call([
            // 1. Datos base
            AbsenceTypeSeeder::class,
            SubscriptionSettingsSeeder::class,
            RoleSeeder::class, // Roles del sistema

            // 2. Configuración
            SubscriptionPlanSeeder::class,
            CompanySettingsSeeder::class,
            AreasSeeder::class,

            // 3. Usuarios (crear después de áreas)
            UserSeeder::class,

            // 4. Ausencias (crear después de usuarios y tipos)
            AbsenceSeeder::class,
        ]);
    }
}
