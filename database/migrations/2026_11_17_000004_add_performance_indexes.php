<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para las consultas más frecuentes (calendario, dashboard,
 * solapamientos, saldos). Aditiva y defensiva: no crea un índice que ya exista.
 */
return new class extends Migration
{
    private array $indexes = [
        'absences' => [
            'absences_tenant_status_start_index' => ['tenant_id', 'status', 'start_datetime'],
            'absences_user_range_index' => ['user_id', 'start_datetime', 'end_datetime'],
        ],
        'users' => [
            'users_tenant_role_active_index' => ['tenant_id', 'role', 'is_active'],
        ],
        'vacation_years' => [
            'vacation_years_user_expires_index' => ['user_id', 'expires_at'],
        ],
        'hr_documents' => [
            'hr_documents_tenant_user_index' => ['tenant_id', 'user_id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name)) {
                    continue;
                }

                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        continue 2;
                    }
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($name) {
                        $blueprint->dropIndex($name);
                    });
                }
            }
        }
    }
};
