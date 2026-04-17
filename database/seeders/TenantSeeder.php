<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AbsenceType;
use App\Models\Area;
use App\Models\CompanySettings;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // =================================================================
        // TENANT PRINCIPAL (MAIN) - Tu empresa que administra todo
        // =================================================================
        $mainTenant = Tenant::create([
            'name' => 'Mi Empresa Principal',
            'slug' => 'mi-empresa',
            'domain' => null, // o 'miempresa.tudominio.com' para dominio personalizado
            'email' => 'admin@miempresa.com',
            'is_main' => true,
            'is_active' => true,
            'timezone' => 'America/Bogota',
            'locale' => 'es',
        ]);

        // Settings para tenant principal
        CompanySettings::create([
            'tenant_id' => $mainTenant->id,
            'company_name' => 'Mi Empresa Principal SAS',
            'company_email' => 'admin@miempresa.com',
            'vacation_days_default' => 15,
            'vacation_days_advance' => 30,
            'workday_start' => '08:00',
            'workday_end' => '17:00',
        ]);

        // Usuario superadmin del tenant principal
        $mainAdmin = User::create([
            'tenant_id' => $mainTenant->id,
            'name' => 'Admin Principal',
            'first_name' => 'Admin',
            'last_name' => 'Principal',
            'email' => 'admin@miempresa.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::SUPERADMIN,
        ]);

        // Áreas del tenant principal
        $hrArea = Area::create([
            'tenant_id' => $mainTenant->id,
            'name' => 'Recursos Humanos',
            'color' => '#3b82f6',
            'display_order' => 1,
            'created_by' => $mainAdmin->id,
        ]);

        $itArea = Area::create([
            'tenant_id' => $mainTenant->id,
            'name' => 'Tecnología',
            'color' => '#10b981',
            'display_order' => 2,
            'created_by' => $mainAdmin->id,
        ]);

        // Tipos de ausencia para tenant principal
        AbsenceType::create([
            'tenant_id' => $mainTenant->id,
            'name' => 'Vacaciones',
            'deducts_vacation' => true,
            'requires_approval' => true,
            'default_include_saturday' => false,
            'default_include_sunday' => false,
            'default_include_holidays' => true,
            'color' => '#22c55e',
        ]);

        AbsenceType::create([
            'tenant_id' => $mainTenant->id,
            'name' => 'Enfermedad',
            'deducts_vacation' => false,
            'requires_approval' => true,
            'color' => '#ef4444',
        ]);

        // =================================================================
        // TENANT 2 - Empresa ejemplo 1
        // =================================================================
        $tenant2 = Tenant::create([
            'name' => 'Tech Solutions Colombia',
            'slug' => 'tech-solutions',
            'domain' => null,
            'email' => 'admin@techsolutions.co',
            'is_main' => false,
            'is_active' => true,
            'timezone' => 'America/Bogota',
            'locale' => 'es',
        ]);

        CompanySettings::create([
            'tenant_id' => $tenant2->id,
            'company_name' => 'Tech Solutions Colombia SAS',
            'company_email' => 'admin@techsolutions.co',
            'vacation_days_default' => 18,
            'vacation_days_advance' => 45,
            'workday_start' => '07:00',
            'workday_end' => '16:00',
        ]);

        // =================================================================
        // TENANT 3 - Empresa ejemplo 2
        // =================================================================
        $tenant3 = Tenant::create([
            'name' => 'Consultora Andina',
            'slug' => 'consultora-andina',
            'domain' => 'andina.consultora.com', // Dominio personalizado
            'email' => 'admin@andina.consultora.com',
            'is_main' => false,
            'is_active' => true,
            'timezone' => 'America/Lima',
            'locale' => 'es',
        ]);

        CompanySettings::create([
            'tenant_id' => $tenant3->id,
            'company_name' => 'Consultora Andina SAC',
            'company_email' => 'admin@andina.consultora.com',
            'vacation_days_default' => 20,
            'vacation_days_advance' => 60,
            'workday_start' => '09:00',
            'workday_end' => '18:00',
        ]);

        $this->command->info('✅ Tenants de ejemplo creados:');
        $this->command->info('  - Mi Empresa Principal (main, slug: mi-empresa)');
        $this->command->info('  - Tech Solutions Colombia (slug: tech-solutions)');
        $this->command->info('  - Consultora Andina (domain: andina.consultora.com)');
    }
}
