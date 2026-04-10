<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\User;
use App\Models\VacationYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $type = $request->get('type', 'absences');
        $start = $request->get('start', now()->startOfYear()->toDateString());
        $end = $request->get('end', now()->endOfYear()->toDateString());
        $userId = $request->get('user_id');
        $areaId = $request->get('area_id');

        return match ($type) {
            'absences' => $this->absencesReport($start, $end, $userId, $areaId),
            'vacations' => $this->vacationsReport($start, $end, $userId, $areaId),
            'summary' => $this->summaryReport($start, $end, $areaId),
            default => response()->json(['error' => 'Tipo de reporte inválido'], 400),
        };
    }

    protected function absencesReport(string $start, string $end, ?int $userId, ?int $areaId): \Illuminate\Http\JsonResponse
    {
        $query = Absence::with(['user', 'type', 'approver'])
            ->whereBetween('start_datetime', [$start, $end]);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($areaId) {
            $query->whereHas('user', fn ($q) => $q->where('area_id', $areaId));
        }

        $absences = $query->orderBy('start_datetime')->get();

        $data = $absences->map(fn ($a) => [
            'id' => $a->id,
            'empleado' => $a->user->name,
            'area' => $a->user->area?->name,
            'tipo' => $a->type->name,
            'inicio' => $a->start_datetime->toDateString(),
            'fin' => $a->end_datetime->toDateString(),
            'dias' => $a->total_days,
            'horas' => $a->total_hours,
            'estado' => $a->status->value,
            'aprobado_por' => $a->approver?->name,
            'aprobado_en' => $a->approved_at?->toDateTimeString(),
            'creado_en' => $a->created_at->toDateTimeString(),
        ]);

        return response()->json([
            'type' => 'absences',
            'period' => ['start' => $start, 'end' => $end],
            'filters' => array_filter(['user_id' => $userId, 'area_id' => $areaId]),
            'total' => $absences->count(),
            'data' => $data,
        ]);
    }

    protected function vacationsReport(string $start, string $end, ?int $userId, ?int $areaId): \Illuminate\Http\JsonResponse
    {
        $query = VacationYear::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($areaId) {
            $query->whereHas('user', fn ($q) => $q->where('area_id', $areaId));
        }

        if (! $userId) {
            $query->whereBetween('year', [Carbon::parse($start)->year, Carbon::parse($end)->year]);
        }

        $years = $query->orderByDesc('year')->get();

        $data = $years->map(fn ($y) => [
            'id' => $y->id,
            'empleado' => $y->user->name,
            'area' => $y->user->area?->name,
            'año' => $y->year,
            'asignados' => $y->allocated_days,
            'usados' => $y->used_days,
            'disponibles' => $y->availableDays(),
            'vencimiento' => $y->expires_at->toDateString(),
            'vencido' => $y->isExpired(),
        ]);

        return response()->json([
            'type' => 'vacations',
            'period' => ['start' => $start, 'end' => $end],
            'filters' => array_filter(['user_id' => $userId, 'area_id' => $areaId]),
            'total' => $years->count(),
            'data' => $data,
        ]);
    }

    protected function summaryReport(string $start, string $end, ?int $areaId): \Illuminate\Http\JsonResponse
    {
        $userQuery = User::where('role', '!=', 'admin');

        if ($areaId) {
            $userQuery->where('area_id', $areaId);
        }

        $employees = $userQuery->get();

        $absencesQuery = Absence::whereBetween('start_datetime', [$start, $end]);

        if ($areaId) {
            $absencesQuery->whereHas('user', fn ($q) => $q->where('area_id', $areaId));
        }

        $absences = $absencesQuery->get();

        $byStatus = $absences->groupBy('status')->map(fn ($g) => $g->count());
        $byType = $absences->groupBy(fn ($a) => $a->type->name)->map(fn ($g) => [
            'count' => $g->count(),
            'days' => $g->sum('total_days'),
        ]);

        $vacationStats = $employees->map(fn ($e) => [
            'name' => $e->name,
            'area' => $e->area?->name,
            'allocated' => $e->vacationYears()->sum('allocated_days'),
            'used' => $e->vacationYears()->sum('used_days'),
            'available' => $e->availableVacationDays(),
        ]);

        return response()->json([
            'type' => 'summary',
            'period' => ['start' => $start, 'end' => $end],
            'filters' => array_filter(['area_id' => $areaId]),
            'absences' => [
                'total' => $absences->count(),
                'total_days' => $absences->sum('total_days'),
                'by_status' => $byStatus,
                'by_type' => $byType,
            ],
            'employees' => [
                'total' => $employees->count(),
                'vacation_summary' => $vacationStats,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'absences');
        $format = $request->get('format', 'csv');
        $start = $request->get('start', now()->startOfYear()->toDateString());
        $end = $request->get('end', now()->endOfYear()->toDateString());
        $userId = $request->get('user_id');
        $areaId = $request->get('area_id');

        $response = $this->index($request)->getData(true);
        $data = $response['data'] ?? [];

        $filename = "reporte-{$type}-{$start}-{$end}".($areaId ? "-area-{$areaId}" : '');

        return match ($format) {
            'csv' => $this->exportCsv($data, $filename),
            default => $this->exportCsv($data, $filename),
        };
    }

    protected function exportCsv(array $data, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($data) {
            if (empty($data)) {
                echo "\n";

                return;
            }

            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($data[0]), ';');

            foreach ($data as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
