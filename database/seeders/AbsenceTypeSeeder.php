<?php

namespace Database\Seeders;

use App\Models\AbsenceType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class AbsenceTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el tenant principal
        $mainTenant = Tenant::where('is_main', true)->first();

        if (! $mainTenant) {
            $this->command->warn('⚠️ No hay tenant principal. Ejecutá TenantSeeder primero.');

            return;
        }

        // Limpiar tipos existentes del tenant principal
        AbsenceType::where('tenant_id', $mainTenant->id)->delete();

        AbsenceType::insert([
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'Vacaciones',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => false,
                'default_include_saturday' => false,
                'default_include_sunday' => false,
                'default_include_holidays' => false,
                'color' => '#3498db',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'Dia libre',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#2ecc71',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'Permiso',
                'deducts_vacation' => false,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#f39c12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $mainTenant->id,
                'name' => 'Cumpleaños',
                'deducts_vacation' => false,
                'requires_approval' => false,
                'counts_as_hours' => false,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#9b59b6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ AbsenceType sembrado para tenant principal');
    }
}
