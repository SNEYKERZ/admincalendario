<?php

namespace App\Http\Controllers;

use App\Services\OvertimeReportService;
use Illuminate\Http\Request;

class OvertimeReportController extends Controller
{
    public function __construct(
        protected OvertimeReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'area_id', 'start_date', 'end_date']);
        $reportData = $this->reportService->getReportData($filters);
        $summaryByUser = $this->reportService->getSummaryByUser($filters);
        $summaryByArea = $this->reportService->getSummaryByArea($filters);

        return response()->json([
            'detailed' => $reportData,
            'by_user' => $summaryByUser,
            'by_area' => $summaryByArea,
        ]);
    }

    public function exportExcel(Request $request)
    {
        try {
            $filters = $request->only(['user_id', 'area_id', 'start_date', 'end_date']);
            $reportData = $this->reportService->getReportData($filters);

            if ($reportData->isEmpty()) {
                return response()->json(['message' => 'No hay datos para exportar'], 204);
            }

            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="Reporte_Horas_Extra_' . now()->format('Y-m-d_His') . '.xlsx"',
            ];

            $csv = $this->generateCsv($reportData);

            return response($csv, 200, $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->only(['user_id', 'area_id', 'start_date', 'end_date']);
            $reportData = $this->reportService->getReportData($filters);

            if ($reportData->isEmpty()) {
                return response()->json(['message' => 'No hay datos para exportar'], 204);
            }

            $html = $this->generatePdfHtml($reportData);

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="Reporte_Horas_Extra_' . now()->format('Y-m-d_His') . '.html"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function generateCsv($data): string
    {
        // Encabezados según FORMATO_HE_2026.xlsx
        $output = "Empleado,ID,Fecha,Día Semana,Día,Mes,Hora Inicio,Hora Fin,Cliente,Proyecto,Motivo,Tiempo (horas),Área,Estado,Aprobado Por,Notas\n";

        foreach ($data as $row) {
            $output .= "\"{$row['empleado']}\",{$row['id_empleado']},\"{$row['fecha']}\",\"{$row['dia_semana']}\",{$row['dia_mes']},\"{$row['mes']}\",\"{$row['hora_inicio']}\",\"{$row['hora_fin']}\",\"{$row['cliente']}\",\"{$row['proyecto']}\",\"{$row['motivo']}\",{$row['tiempo_horas']},\"{$row['area']}\",\"{$row['estado']}\",\"{$row['aprobado_por']}\",\"{$row['notas']}\"\n";
        }

        return $output;
    }

    protected function generatePdfHtml($data): string
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Horas Extra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <h1>Reporte de Horas Extra</h1>
    <p>Generado el: HTML

        . date('d/m/Y H:i:s') . '</p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Empleado</th>
                <th>Área</th>
                <th>Horas</th>
                <th>Estado</th>
                <th>Aprobado Por</th>
            </tr>
        </thead>
        <tbody>
HTML;

        foreach ($data as $row) {
            $html .= <<<HTML
            <tr>
                <td>{$row['fecha']}</td>
                <td>{$row['empleado']}</td>
                <td>{$row['area']}</td>
                <td style="text-align: right;">{$row['horas']}h</td>
                <td>{$row['estado']}</td>
                <td>{$row['aprobado_por']}</td>
            </tr>
HTML;
        }

        $html .= <<<'HTML'
        </tbody>
    </table>
    <div class="footer">
        <p>Este reporte fue generado automáticamente por el sistema de gestión de horas extra.</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}
