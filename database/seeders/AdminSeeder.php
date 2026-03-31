<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create superadmin user only if not exists
        if (! User::where('email', 'superadmin@admincalendar.com')->exists()) {
            User::create([
                'name' => 'Super Administrador',
                'first_name' => 'Super',
                'last_name' => 'Administrador',
                'email' => 'superadmin@admincalendar.com',
                'password' => Hash::make('superadmin123'),
                'role' => UserRole::SUPERADMIN->value,
                'birth_date' => '1985-01-01',
                'hire_date' => now(),
            ]);
        }

        // Create admin user only if not exists
        if (! User::where('email', 'admin@admincalendar.com')->exists()) {
            User::create([
                'name' => 'Administrador',
                'first_name' => 'Admin',
                'last_name' => 'Principal',
                'email' => 'admin@admincalendar.com',
                'password' => Hash::make('admin123'),
                'role' => UserRole::ADMIN->value,
                'birth_date' => '1990-01-01',
                'hire_date' => now(),
            ]);
        }

        // Create default subscription settings only if not exists
        if (! SubscriptionSettings::exists()) {
            SubscriptionSettings::create([
                'conversion_rate' => 0.00025,
                'cop_reference_amount' => 20000,
                'usd_reference_amount' => 5,
                'show_ads_days_before' => 5,
                'enable_payments' => false,
            ]);
        }

        // Create subscription plans only if none exist
        if (SubscriptionPlan::count() === 0) {
            // Plan Starter (Mensual)
            SubscriptionPlan::create([
                'name' => 'Plan Starter',
                'duration_days' => 30,
                'price_cop' => 63000,
                'price_usd' => 15.75,
                'discount_percentage' => 10,
                'original_price_cop' => 70000,
                'original_price_usd' => 17.50,
                'is_active' => true,
                'display_order' => 1,
                'description' => "Ideal para empresas pequeñas o prueba inicial.\n\nIncluye:\n- Dashboard con vista general por colaboradores\n- Calendario de ausencias y vacaciones\n- Solicitudes (aprobar, rechazar, pendiente)\n- Gestión básica de usuarios\n- Reglas de días (festivos, fines de semana)\n\nControl básico y eficiente de vacaciones y ausencias en un solo lugar.",
            ]);

            // Plan Business (Semestral)
            SubscriptionPlan::create([
                'name' => 'Plan Business',
                'duration_days' => 180,
                'price_cop' => 324000,
                'price_usd' => 81.00,
                'discount_percentage' => 10,
                'original_price_cop' => 360000,
                'original_price_usd' => 90.00,
                'is_active' => true,
                'display_order' => 2,
                'description' => "Ideal para empresas en crecimiento.\n\nIncluye TODO lo anterior +\n- Reportes detallados por usuario y tipo de ausencia\n- Personalización empresarial (logo, NIT, nombre)\n- Mejor control del calendario y reglas avanzadas\n- Mayor capacidad de gestión de usuarios\n\nGestión profesional del talento con reportes y personalización corporativa.",
            ]);

            // Plan Enterprise (Anual)
            SubscriptionPlan::create([
                'name' => 'Plan Enterprise',
                'duration_days' => 365,
                'price_cop' => 540000,
                'price_usd' => 135.00,
                'discount_percentage' => 10,
                'original_price_cop' => 600000,
                'original_price_usd' => 150.00,
                'is_active' => true,
                'display_order' => 3,
                'description' => "Ideal para empresas que quieren control total.\n\nIncluye TODO lo anterior +\n- Configuración avanzada de la empresa\n- Control completo del sistema por plan (módulos activos)\n- Acceso a gestión tipo superadmin\n- Mayor optimización y escalabilidad\n\nSolución completa y escalable para la gestión integral del tiempo del personal.",
            ]);
        }
    }
}
