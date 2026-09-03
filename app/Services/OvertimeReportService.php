<?php

namespace App\Services;

use App\Models\OvertimeHours;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OvertimeReportService
{
    public function getReportData(array $filters): Collection
    {
        $authUser = auth()->user();

        $query = OvertimeHours::with(['user', 'approver', 'user.area'])
            ->where('status', 'approved');

        if (!$authUser->isSuperAdmin() && $authUser->isAdmin()) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('area_id', $authUser->area_id);
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['area_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('area_id', $filters['area_id']);
            });
        }

        if (!empty($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        return $query->get()
            ->sortBy(function ($overtime) {
                return [$overtime->user->area?->name ?? 'Z', $overtime->user->name];
            })
            ->values()
            ->map(function ($overtime) {
            return [
                'empleado' => $overtime->user->name,
                'id_empleado' => $overtime->user->id,
                'fecha' => $overtime->date->format('Y-m-d'),
                'dia_semana' => $overtime->day_of_week,
                'dia_mes' => $overtime->day_of_month,
                'mes' => $overtime->month,
                'hora_inicio' => $overtime->start_time,
                'hora_fin' => $overtime->end_time,
                'cliente' => $overtime->client ?? '',
                'proyecto' => $overtime->project ?? '',
                'motivo' => $overtime->reason,
                'tiempo_horas' => number_format($overtime->hours, 2),
                'area' => $overtime->user->area?->name ?? 'N/A',
                'estado' => ucfirst($overtime->status),
                'aprobado_por' => $overtime->approver?->name ?? 'Sistema',
                'notas' => $overtime->approval_notes ?? '',
            ];
        });
    }

    public function getSummaryByUser(array $filters): Collection
    {
        $query = OvertimeHours::with(['user'])
            ->where('status', 'approved');

        if (!empty($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        $groupedByUser = $query->get()->groupBy('user_id');

        return $groupedByUser->map(function ($userOvertimes) {
            $totalHours = $userOvertimes->sum('hours');
            $totalDays = $userOvertimes->count();
            $user = $userOvertimes->first()->user;

            return [
                'empleado' => $user->name,
                'area' => $user->area?->name ?? 'N/A',
                'total_horas' => number_format($totalHours, 2),
                'total_dias' => $totalDays,
                'promedio_horas' => number_format($totalHours / $totalDays, 2),
            ];
        })->values();
    }

    public function getSummaryByArea(array $filters): Collection
    {
        $query = OvertimeHours::with(['user', 'user.area'])
            ->where('status', 'approved');

        if (!empty($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        $groupedByArea = $query->get()->groupBy(function ($overtime) {
            return $overtime->user->area_id ?? 'sin_area';
        });

        return $groupedByArea->map(function ($areaOvertimes) {
            $firstArea = $areaOvertimes->first()->user->area;
            $totalHours = $areaOvertimes->sum('hours');

            return [
                'area' => $firstArea?->name ?? 'Sin Área',
                'total_horas' => number_format($totalHours, 2),
                'total_registros' => $areaOvertimes->count(),
                'promedio_horas' => number_format($totalHours / $areaOvertimes->count(), 2),
            ];
        })->values();
    }
}
