<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\OvertimeHours;
use App\Models\User;
use App\Services\OvertimeReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeReportsController extends Controller
{
    public function __construct(
        protected OvertimeReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $authUser = Auth::user();

        $query = OvertimeHours::with(['user', 'user.area', 'approver'])
            ->where('status', 'approved');

        if (!$authUser->isSuperAdmin() && $authUser->isAdmin()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('area_id', $authUser->area_id);
            });
        }

        $employees = User::where('role', 'employee')
            ->where('is_active', true)
            ->when($authUser->isAdmin() && !$authUser->isSuperAdmin(), function ($q) use ($authUser) {
                return $q->where('area_id', $authUser->area_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'area_id']);

        $areas = Area::when($authUser->isAdmin() && !$authUser->isSuperAdmin(), function ($q) use ($authUser) {
            return $q->where('id', $authUser->area_id);
        })
            ->orderBy('name')
            ->get(['id', 'name']);

        $reportData = $this->reportService->getReportData($request->only(['user_id', 'area_id', 'start_date', 'end_date']));
        $summaryByUser = $this->reportService->getSummaryByUser($request->only(['start_date', 'end_date']));
        $summaryByArea = $this->reportService->getSummaryByArea($request->only(['start_date', 'end_date']));

        return inertia('OvertimeReports/Index', [
            'reportData' => $reportData,
            'summaryByUser' => $summaryByUser,
            'summaryByArea' => $summaryByArea,
            'employees' => $employees,
            'areas' => $areas,
        ]);
    }

    public function export(Request $request)
    {
        $authUser = Auth::user();

        $filters = $request->only(['user_id', 'area_id', 'start_date', 'end_date']);

        $reportData = $this->reportService->getReportData($filters);

        if ($reportData->isEmpty()) {
            return response()->json(['message' => 'No hay datos para exportar'], 204);
        }

        $csv = $this->generateCsv($reportData);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="Reporte_Horas_Extra_' . now()->format('Y-m-d_His') . '.csv"',
        ]);
    }

    protected function generateCsv($data): string
    {
        $output = "Área,Empleado,ID,Fecha,Día Semana,Día,Mes,Hora Inicio,Hora Fin,Cliente,Proyecto,Motivo,Tiempo (horas),Estado,Aprobado Por,Notas\n";

        foreach ($data as $row) {
            $output .= "\"{$row['area']}\",\"{$row['empleado']}\",{$row['id_empleado']},\"{$row['fecha']}\",\"{$row['dia_semana']}\",{$row['dia_mes']},\"{$row['mes']}\",\"{$row['hora_inicio']}\",\"{$row['hora_fin']}\",\"{$row['cliente']}\",\"{$row['proyecto']}\",\"{$row['motivo']}\",{$row['tiempo_horas']},\"{$row['estado']}\",\"{$row['aprobado_por']}\",\"{$row['notas']}\"\n";
        }

        return $output;
    }
}
