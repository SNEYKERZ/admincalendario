<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\OvertimeHours;
use App\Models\User;
use App\Services\OvertimeHoursService;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\PatternFill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Http\Request;

class AdminOvertimeHoursController extends Controller
{
    protected OvertimeHoursService $service;

    public function __construct(OvertimeHoursService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $this->authorize('admin');

        $areaId = $request->get('area_id', '');
        $month = $request->get('month', '');
        $year = $request->get('year', date('Y'));
        $userId = $request->get('user_id', '');

        $query = auth()->user()->tenant
            ->overtimeHours()
            ->with(['user.area', 'approver'])
            ->orderBy('date', 'desc');

        if ($areaId) {
            $query->whereHas('user', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
        }

        if ($month && $year) {
            $query->whereYear('date', $year)
                ->whereMonth('date', $month);
        } elseif ($year) {
            $query->whereYear('date', $year);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $overtimes = $query->paginate(20);

        $tenant = auth()->user()->tenant;
        $areas = Area::where('tenant_id', $tenant->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $employees = User::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('AdminOvertimeHours/Index', [
            'overtimes' => $overtimes,
            'areas' => $areas,
            'employees' => $employees,
            'filters' => [
                'area_id' => $areaId,
                'month' => $month,
                'year' => $year,
                'user_id' => $userId,
            ],
        ]);
    }

    public function update(OvertimeHours $overtimeHours)
    {
        $this->authorize('admin');

        $validated = request()->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'client' => 'nullable|string',
            'project' => 'nullable|string',
            'reason' => 'required|string|min:10',
        ]);

        $updated = $this->service->update($overtimeHours, $validated);

        return back()->with('success', 'Registro actualizado correctamente');
    }

    public function approve(OvertimeHours $overtimeHours)
    {
        $this->authorize('admin');

        if (!$overtimeHours->isPending()) {
            return back()->with('error', 'Solo se pueden aprobar registros pendientes');
        }

        $this->service->approve($overtimeHours, auth()->user());

        return back()->with('success', 'Registro aprobado correctamente');
    }

    public function reject(OvertimeHours $overtimeHours)
    {
        $this->authorize('admin');

        $validated = request()->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$overtimeHours->isPending()) {
            return back()->with('error', 'Solo se pueden rechazar registros pendientes');
        }

        $this->service->reject($overtimeHours, auth()->user(), $validated['reason']);

        return back()->with('success', 'Registro rechazado correctamente');
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('admin');

        $areaId = $request->get('area_id', '');
        $month = $request->get('month', '');
        $year = $request->get('year', date('Y'));
        $userId = $request->get('user_id', '');

        $query = auth()->user()->tenant
            ->overtimeHours()
            ->with(['user.area', 'approver'])
            ->orderBy('date', 'asc');

        if ($areaId) {
            $query->whereHas('user', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            });
        }

        if ($month && $year) {
            $query->whereYear('date', $year)
                ->whereMonth('date', $month);
        } elseif ($year) {
            $query->whereYear('date', $year);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $overtimes = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Horas Extra');

        $headers = [
            'Empleado',
            'Área',
            'Fecha',
            'Día',
            'Mes',
            'Horas',
            'Proyecto',
            'Cliente',
            'Motivo',
            'Estado',
            'Aprobado Por',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => PatternFill::FILL_SOLID, 'startColor' => ['rgb' => '1F67C9']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ];

        foreach (range('A', 'K') as $column) {
            $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
        }

        $row = 2;
        foreach ($overtimes as $overtime) {
            $sheet->setCellValue('A' . $row, $overtime->user->name);
            $sheet->setCellValue('B' . $row, $overtime->user->area?->name ?? '-');
            $sheet->setCellValue('C' . $row, $overtime->date->format('Y-m-d'));
            $sheet->setCellValue('D' . $row, $overtime->day_of_week);
            $sheet->setCellValue('E' . $row, $overtime->month);
            $sheet->setCellValue('F' . $row, $overtime->hours);
            $sheet->setCellValue('G' . $row, $overtime->project ?? '-');
            $sheet->setCellValue('H' . $row, $overtime->client ?? '-');
            $sheet->setCellValue('I' . $row, $overtime->reason);
            $sheet->setCellValue('J' . $row, ucfirst($overtime->status));
            $sheet->setCellValue('K' . $row, $overtime->approver?->name ?? '-');
            $row++;
        }

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'horas_extra_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
