<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixUtf8Encoding extends Command
{
    protected $signature = 'app:fix-utf8-encoding';
    protected $description = 'Corrige problemas de codificación UTF-8 en la base de datos';

    public function handle()
    {
        $this->info('=== Corrigiendo Codificación UTF-8 ===');

        try {
            // 1. Convertir base de datos
            $database = env('DB_DATABASE');
            DB::statement("ALTER DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("✓ Base de datos convertida a utf8mb4");

            // 2. Convertir todas las tablas
            $this->info("\n1. Convirtiendo tablas a utf8mb4...");
            $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");

            foreach ($tables as $table) {
                $tableName = $table->TABLE_NAME;
                try {
                    DB::statement("ALTER TABLE `$tableName` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $this->line("✓ Tabla '$tableName' convertida");
                } catch (\Exception $e) {
                    $this->error("✗ Error en tabla '$tableName': " . $e->getMessage());
                }
            }

            // 3. Reparar datos corruptos (caracteres UTF-8 mal codificados)
            $this->info("\n2. Reparando caracteres corruptos...");

            $replacements = [
                'Ã©' => 'é',
                'Ã¡' => 'á',
                'Ã­' => 'í',
                'ó' => 'ó',
                'Ã' => 'Á',
                'Ã‰' => 'É',
                'Ã' => 'Í',
                'Ã"' => 'Ó',
                'Ã' => 'Ú',
                'Ã±' => 'ñ',
                'Ã' => 'Ñ',
                'Ã¼' => 'ü',
                'Ã' => 'Ü',
                'Â°' => '°',
                'â€ ' => ' ',
                'Â»' => '»',
            ];

            $totalReplaced = 0;

            foreach ($tables as $tableObj) {
                $table = $tableObj->TABLE_NAME;
                try {
                    $columns = DB::select(
                        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = '$table'
                        AND (COLUMN_TYPE LIKE '%char%' OR COLUMN_TYPE LIKE '%text%')"
                    );

                    foreach ($columns as $column) {
                        $columnName = $column->COLUMN_NAME;

                        foreach ($replacements as $corrupt => $correct) {
                            try {
                                $affected = DB::update(
                                    "UPDATE `$table` SET `$columnName` = REPLACE(`$columnName`, ?, ?) WHERE `$columnName` LIKE ?",
                                    [$corrupt, $correct, "%$corrupt%"]
                                );

                                if ($affected > 0) {
                                    $totalReplaced += $affected;
                                    $this->line("✓ Reemplazados $affected registros de '$corrupt' → '$correct' en '$table'.'$columnName'");
                                }
                            } catch (\Exception $e) {
                                // Ignorar errores menores
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("✗ Error procesando tabla '$table': " . $e->getMessage());
                }
            }

            $this->info("\n✅ Corrección de codificación UTF-8 completada");
            $this->info("Total de caracteres reparados: $totalReplaced");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
