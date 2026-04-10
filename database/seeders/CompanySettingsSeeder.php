<?php

namespace Database\Seeders;

use App\Models\CompanySettings;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        CompanySettings::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Ausentra - Gestión de Ausencias',
                'company_identification' => 'NIT: 901.234.567-1',
                'company_email' => 'contacto@ausentra.com',
                'company_phone' => '+57 300 123 4567',
                'company_address' => 'Carrera 43A #1-50, Torre Empresarial, piso 15, Bogotá D.C., Colombia',
                'company_logo' => null,
                'vacation_days_default' => 15,
                'vacation_days_advance' => 30,
                'workday_start' => '08:00',
                'workday_end' => '17:00',
                'allow_weekend_absences' => false,
                'allow_holiday_absences' => false,
                'require_approval_for_all' => true,
                'notification_email_enabled' => true,
            ]
        );

        $this->command->info('✅ CompanySettings sembrado correctamente');
    }
}
