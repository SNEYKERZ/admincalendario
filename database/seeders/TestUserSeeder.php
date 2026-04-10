<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Admin Test',
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN->value,
            'is_active' => true,
        ]);

        \App\Models\User::create([
            'name' => 'Usuario Prueba',
            'first_name' => 'Usuario',
            'last_name' => 'Prueba',
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::COLLABORATOR->value,
            'is_active' => true,
        ]);

        \App\Models\CompanySettings::create([
            'company_name' => 'Mi Empresa',
            'vacation_days_default' => 15,
            'vacation_days_advance' => 30,
            'workday_start' => '08:00',
            'workday_end' => '17:00',
            'allow_weekend_absences' => false,
            'allow_holiday_absences' => false,
            'require_approval_for_all' => true,
            'notification_email_enabled' => true,
        ]);
    }
}
