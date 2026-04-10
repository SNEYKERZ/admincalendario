<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'superadmin',
                'display_name' => 'Superadministrador',
                'description' => 'Acceso completo al sistema, incluyendo gestión de empresas y configuración global',
                'color' => '#7C3AED',
                'is_system' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Gestión de usuarios, áreas, aprobación de ausencias y configuración de la empresa',
                'color' => '#2563EB',
                'is_system' => true,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
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
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('✅ Roles base sembrados: Superadmin, Admin, Colaborador');
    }
}
