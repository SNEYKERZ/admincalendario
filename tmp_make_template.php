<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Usuarios');

$headers = [
    'nombre',
    'apellidos',
    'identificacion',
    'genero',
    'correo',
    'numero_celular',
    'area',
    'rol',
    'fecha_nacimiento',
    'fecha_contratacion',
];

$sheet->fromArray($headers, null, 'A1');

$rows = [
    ['Juan', 'Perez', '1001001001', 'masculino', 'juan.perez@empresa.com', '3001234567', 'Recursos Humanos', 'colaborador', '1993-05-21', '2024-01-15'],
    ['Maria', 'Gomez', '1001001002', 'femenino', 'maria.gomez@empresa.com', '3007654321', 'Finanzas', 'admin', '1990-11-08', '2023-08-01'],
    ['Carlos', 'Rincon', '1001001003', 'otro', 'carlos.rincon@empresa.com', '3012223344', 'Tecnologia', 'colaborador', '1998-02-12', '2025-02-10'],
];

$sheet->fromArray($rows, null, 'A2');

foreach (range('A', 'J') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$outputDir = __DIR__ . '/storage/app/public';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$outputFile = $outputDir . '/plantilla_cargue_masivo_usuarios.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputFile);

echo $outputFile . PHP_EOL;
