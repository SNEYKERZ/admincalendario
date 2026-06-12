<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\Area;
use App\Models\User;
use App\Services\ApprovalChainService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ApprovalChainDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = \App\Models\Tenant::first();

        if (!$tenant) {
            return;
        }

        // Crear jefe de área para IT
        $itManager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Carlos Manager IT',
            'email' => 'carlos.manager@company.com',
            'role' => 'admin',
            'is_area_manager' => true,
        ]);

        // Crear jefe de área para HR
        $hrManager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'María Manager HR',
            'email' => 'maria.manager@company.com',
            'role' => 'admin',
            'is_area_manager' => true,
        ]);

        // Asignar managers a áreas
        $itArea = Area::where('name', 'IT')->orWhere('name', 'it')->first();
        if ($itArea) {
            $itArea->update(['area_manager_id' => $itManager->id]);
        }

        $hrArea = Area::where('name', 'HR')->orWhere('name', 'Recursos Humanos')->first();
        if ($hrArea) {
            $hrArea->update(['area_manager_id' => $hrManager->id]);
        }

        // Crear empleados sin jefe de área
        $employees = User::where('role', 'colaborador')
            ->where('tenant_id', $tenant->id)
            ->limit(5)
            ->get();

        $approvalService = app(ApprovalChainService::class);

        // Crear ausencias pendientes para testing
        foreach ($employees as $employee) {
            if (!$employee->area_id) {
                continue;
            }

            $absence = Absence::create([
                'user_id' => $employee->id,
                'absence_type_id' => 1, // Vacaciones
                'start_datetime' => Carbon::now()->addDays(5),
                'end_datetime' => Carbon::now()->addDays(10),
                'total_days' => 5,
                'total_hours' => 0,
                'include_saturday' => false,
                'include_sunday' => false,
                'include_holidays' => false,
                'holiday_country' => 'CO',
                'status' => 'pendiente',
                'tenant_id' => $tenant->id,
            ]);

            // Crear cadena de aprobación
            $approvalService->createApprovalChain($absence);
        }

        $this->command->info('Demo data created for approval chains');
    }
}
