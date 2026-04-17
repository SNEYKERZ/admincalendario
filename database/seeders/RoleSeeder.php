<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el tenant principal
        $mainTenant = Tenant::where('is_main', true)->first();

        if (! $mainTenant) {
            $this->command->warn('⚠️ No hay tenant principal. Ejecutá TenantSeeder primero.');

            return;
        }

        // Limpiar roles existentes del tenant principal
        Role::where('tenant_id', $mainTenant->id)->delete();

        $roles = [
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'superadmin',
                'display_name' => 'Superadministrador',
                'description' => 'Acceso completo al sistema, incluyendo gestión de empresas y configuración global',
                'color' => '#7C3AED',
                'is_system' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Gestión de usuarios, áreas, aprobación de ausencias y configuración de la empresa',
                'color' => '#2563EB',
                'is_system' => true,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'colaborador',
                'display_name' => 'Colaborador',
                'description' => 'Usuario regular que puede solicitar ausencias y ver su historial',
                'color' => '#10B981',
                'is_system' => true,
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        $this->command->info('✅ Roles base sembrados para tenant principal: Superadmin, Admin, Colaborador');
    }
}
