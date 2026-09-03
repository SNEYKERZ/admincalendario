<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Crear módulos
        $dashboard = Module::firstOrCreate(
            ['slug' => 'dashboard'],
            ['name' => 'Dashboard', 'icon' => 'LayoutDashboard', 'is_core' => true, 'display_order' => 1]
        );

        $calendario = Module::firstOrCreate(
            ['slug' => 'calendario'],
            ['name' => 'Calendario', 'icon' => 'LayoutGrid', 'is_core' => true, 'display_order' => 2]
        );

        $comunidad = Module::firstOrCreate(
            ['slug' => 'comunidad'],
            ['name' => 'Comunidad', 'icon' => 'Users', 'is_core' => true, 'display_order' => 3]
        );

        $gestionUsuarios = Module::firstOrCreate(
            ['slug' => 'gestion-usuarios'],
            ['name' => 'Gestión de Usuarios', 'icon' => 'Users', 'is_core' => false, 'display_order' => 4]
        );

        $areas = Module::firstOrCreate(
            ['slug' => 'areas'],
            ['name' => 'Áreas', 'icon' => 'Building2', 'is_core' => false, 'display_order' => 5]
        );

        $reportes = Module::firstOrCreate(
            ['slug' => 'reportes'],
            ['name' => 'Reportes', 'icon' => 'FileBarChart', 'is_core' => false, 'display_order' => 6]
        );

        $documentos = Module::firstOrCreate(
            ['slug' => 'documentos'],
            ['name' => 'Documentos', 'icon' => 'FileText', 'is_core' => false, 'display_order' => 7]
        );

        $configuracion = Module::firstOrCreate(
            ['slug' => 'configuracion-empresa'],
            ['name' => 'Configuración de Empresa', 'icon' => 'Settings', 'is_core' => false, 'display_order' => 8]
        );

        // Agregar módulo de Solicitudes (para Plan Enterprise)
        $solicitudes = Module::firstOrCreate(
            ['slug' => 'solicitudes'],
            ['name' => 'Solicitudes de Empleados', 'icon' => 'FileText', 'is_core' => false, 'display_order' => 9]
        );

        // Agregar módulo de Horas Extra (para Plan Business y Enterprise)
        $horasExtra = Module::firstOrCreate(
            ['slug' => 'horas-extra'],
            ['name' => 'Horas Extra', 'icon' => 'Clock', 'is_core' => false, 'display_order' => 10]
        );

        // Asociar módulos a planes
        $planStarter = SubscriptionPlan::where('name', 'Plan Starter')->first();
        $planBusiness = SubscriptionPlan::where('name', 'Plan Business')->first();
        $planEnterprise = SubscriptionPlan::where('name', 'Plan Enterprise')->first();

        if ($planStarter) {
            $planStarter->modules()->syncWithoutDetaching([
                $dashboard->id,
                $calendario->id,
                $comunidad->id,
                $gestionUsuarios->id,
                $configuracion->id,
            ]);
        }

        if ($planBusiness) {
            $planBusiness->modules()->syncWithoutDetaching([
                $dashboard->id,
                $calendario->id,
                $comunidad->id,
                $gestionUsuarios->id,
                $areas->id,
                $reportes->id,
                $horasExtra->id,
                $configuracion->id,
            ]);
        }

        if ($planEnterprise) {
            $planEnterprise->modules()->syncWithoutDetaching([
                $dashboard->id,
                $calendario->id,
                $comunidad->id,
                $gestionUsuarios->id,
                $areas->id,
                $reportes->id,
                $documentos->id,
                $solicitudes->id,
                $horasExtra->id,
                $configuracion->id,
            ]);
        }

        $this->command->info('✅ Módulos y relaciones con planes creados correctamente');
    }
}
