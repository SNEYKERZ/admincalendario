<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\Area;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ApprovalChainService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApprovalChainDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('is_main', true)->first();

        if (!$tenant) {
            return;
        }

        // Crear jefe de área para Tecnología (idempotente: evita duplicar el email en re-siembras)
        $itManager = User::updateOrCreate(
            ['email' => 'carlos.manager@company.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Carlos Manager IT',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_area_manager' => true,
            ]
        );

        // Crear jefe de área para Recursos Humanos
        $hrManager = User::updateOrCreate(
            ['email' => 'maria.manager@company.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'María Manager HR',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_area_manager' => true,
            ]
        );

        // Asignar managers a áreas (los nombres reales los crea AreasSeeder)
        $itArea = Area::where('tenant_id', $tenant->id)->where('name', 'Tecnología')->first();
        if ($itArea) {
            $itArea->update(['area_manager_id' => $itManager->id]);
        }

        $hrArea = Area::where('tenant_id', $tenant->id)->where('name', 'Recursos Humanos')->first();
        if ($hrArea) {
            $hrArea->update(['area_manager_id' => $hrManager->id]);
        }

        $vacacionesType = AbsenceType::where('tenant_id', $tenant->id)->where('name', 'Vacaciones')->first();

        if (!$vacacionesType) {
            return;
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

            // Evita duplicar la ausencia demo si el seeder se vuelve a correr sin --fresh
            $alreadySeeded = Absence::where('user_id', $employee->id)
                ->where('absence_type_id', $vacacionesType->id)
                ->where('status', 'pendiente')
                ->exists();

            if ($alreadySeeded) {
                continue;
            }

            $absence = Absence::create([
                'user_id' => $employee->id,
                'absence_type_id' => $vacacionesType->id,
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
