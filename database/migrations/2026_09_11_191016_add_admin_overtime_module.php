<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \DB::table('modules')->insert([
            'slug' => 'admin-horas-extra',
            'name' => 'Administración Horas Extra',
            'icon' => 'Clock',
            'is_core' => false,
            'display_order' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asociar el módulo a planes Business y Enterprise
        $adminOvertimeModule = \DB::table('modules')->where('slug', 'admin-horas-extra')->first();
        $planBusiness = \DB::table('subscription_plans')->where('name', 'Plan Business')->first();
        $planEnterprise = \DB::table('subscription_plans')->where('name', 'Plan Enterprise')->first();

        if ($adminOvertimeModule && $planBusiness) {
            \DB::table('plan_modules')->insert([
                'plan_id' => $planBusiness->id,
                'module_id' => $adminOvertimeModule->id,
            ]);
        }

        if ($adminOvertimeModule && $planEnterprise) {
            \DB::table('plan_modules')->insert([
                'plan_id' => $planEnterprise->id,
                'module_id' => $adminOvertimeModule->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::table('modules')->where('slug', 'admin-horas-extra')->delete();
    }
};
