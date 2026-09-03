<?php
/**
 * Script para corregir problemas de codificación UTF-8 en toda la base de datos
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Corrigiendo Codificación UTF-8 ===\n";

try {
    // 1. Convertir todas las tablas a utf8mb4
    echo "\n1. Convirtiendo tablas a utf8mb4...\n";

    $tables = Schema::getTableListing();

    foreach ($tables as $table) {
        try {
            DB::statement("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Tabla '$table' convertida\n";
        } catch (\Exception $e) {
            echo "✗ Error en tabla '$table': " . $e->getMessage() . "\n";
        }
    }

    // 2. Convertir todas las columnas text/varchar a utf8mb4
    echo "\n2. Convirtiendo columnas a utf8mb4...\n";

    foreach ($tables as $table) {
        try {
            $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");

            foreach ($columns as $column) {
                if (in_array($column->COLUMN_TYPE, ['varchar', 'text', 'longtext', 'mediumtext']) || strpos($column->COLUMN_TYPE, 'varchar') !== false || strpos($column->COLUMN_TYPE, 'text') !== false) {
                    if ($column->COLLATION_NAME && strpos($column->COLLATION_NAME, 'utf8mb4') === false) {
                        $columnName = $column->COLUMN_NAME;
                        try {
                            DB::statement("ALTER TABLE `$table` MODIFY `$columnName` " . $column->COLUMN_TYPE . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                            echo "✓ Columna '$table'.'$columnName' convertida\n";
                        } catch (\Exception $e) {
                            echo "✗ Error en '$table'.'$columnName': " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            echo "✗ Error procesando tabla '$table': " . $e->getMessage() . "\n";
        }
    }

    // 3. Reparar datos corruptos
    echo "\n3. Buscando caracteres corruptos...\n";

    $corruptPatterns = [
        'Ã©' => 'é',
        'Ã¡' => 'á',
        'Ã­' => 'í',
        'ó' => 'ó',
        'Ã©' => 'é',
        'Ã' => 'Á',
        'Ã‰' => 'É',
        'Ã' => 'Í',
        'Ã"' => 'Ó',
        'Ã' => 'Ú',
        'Ã±' => 'ñ',
        'Ã' => 'Ñ',
        'Ã¼' => 'ü',
        'Ã' => 'Ü',
    ];

    foreach ($tables as $table) {
        $columns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_TYPE LIKE '%char%' OR COLUMN_TYPE LIKE '%text%'");

        foreach ($columns as $column) {
            $columnName = $column->COLUMN_NAME;

            foreach ($corruptPatterns as $corrupt => $correct) {
                try {
                    DB::statement("UPDATE `$table` SET `$columnName` = REPLACE(`$columnName`, '$corrupt', '$correct') WHERE `$columnName` LIKE '%$corrupt%'");
                    $count = DB::getPdo()->lastRowCount();
                    if ($count > 0) {
                        echo "✓ Reemplazados $count registros de '$corrupt' → '$correct' en '$table'.'$columnName'\n";
                    }
                } catch (\Exception $e) {
                    // Ignorar errores menores
                }
            }
        }
    }

    echo "\n✅ Corrección de codificación UTF-8 completada\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
