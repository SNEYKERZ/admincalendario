<?php

namespace Database\Seeders;

use App\Models\CompanySettings;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Los usuarios, áreas y tipos de ausencia del tenant principal los crean
        // UserSeeder, AreasSeeder y AbsenceTypeSeeder respectivamente; este seeder
        // solo se encarga de que los tenants existan.
        $tenants = [
            [
                'name' => 'Mi Empresa Principal',
                'slug' => 'mi-empresa',
                'domain' => null,
                'email' => 'admin@miempresa.com',
                'is_main' => true,
                'is_active' => true,
                'timezone' => 'America/Bogota',
                'locale' => 'es',
                'settings' => [
                    'company_name' => 'Mi Empresa Principal SAS',
                    'company_email' => 'admin@miempresa.com',
                    'vacation_days_default' => 15,
                    'vacation_days_advance' => 30,
                    'workday_start' => '08:00',
                    'workday_end' => '17:00',
                ],
            ],
            [
                'name' => 'Tech Solutions Colombia',
                'slug' => 'tech-solutions',
                'domain' => null,
                'email' => 'admin@techsolutions.co',
                'is_main' => false,
                'is_active' => true,
                'timezone' => 'America/Bogota',
                'locale' => 'es',
                'settings' => [
                    'company_name' => 'Tech Solutions Colombia SAS',
                    'company_email' => 'admin@techsolutions.co',
                    'vacation_days_default' => 18,
                    'vacation_days_advance' => 45,
                    'workday_start' => '07:00',
                    'workday_end' => '16:00',
                ],
            ],
            [
                'name' => 'Consultora Andina',
                'slug' => 'consultora-andina',
                'domain' => 'andina.consultora.com',
                'email' => 'admin@andina.consultora.com',
                'is_main' => false,
                'is_active' => true,
                'timezone' => 'America/Lima',
                'locale' => 'es',
                'settings' => [
                    'company_name' => 'Consultora Andina SAC',
                    'company_email' => 'admin@andina.consultora.com',
                    'vacation_days_default' => 20,
                    'vacation_days_advance' => 60,
                    'workday_start' => '09:00',
                    'workday_end' => '18:00',
                ],
            ],
        ];

        foreach ($tenants as $data) {
            $settings = $data['settings'];
            unset($data['settings']);

            $tenant = Tenant::updateOrCreate(['slug' => $data['slug']], $data);

            CompanySettings::updateOrCreate(
                ['tenant_id' => $tenant->id],
                array_merge($settings, ['tenant_id' => $tenant->id])
            );
        }

        $this->command->info('✅ Tenants de ejemplo creados:');
        $this->command->info('  - Mi Empresa Principal (main, slug: mi-empresa)');
        $this->command->info('  - Tech Solutions Colombia (slug: tech-solutions)');
        $this->command->info('  - Consultora Andina (domain: andina.consultora.com)');
    }
}
