<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill de tenant_id en tablas que dependen de un usuario.
 *
 * El modelo Absence no declaraba tenant_id en $fillable: el TenantScope no lo
 * filtraba y las ausencias se guardaban con tenant_id NULL (visibles para
 * todas las empresas). Ahora el scope sí aplica, así que los registros
 * existentes deben heredar el tenant de su usuario (o el tenant principal
 * para usuarios legados sin tenant, igual que en tiempo de ejecución).
 *
 * Idempotente: solo toca filas con tenant_id NULL.
 */
return new class extends Migration
{
    private array $tables = [
        'absences',
        'vacation_years',
        'hr_documents',
        'overtime_hours',
        'employee_requests',
    ];

    public function up(): void
    {
        $mainTenantId = Schema::hasTable('tenants')
            ? DB::table('tenants')->where('is_main', true)->orderBy('id')->value('id')
            : null;

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'tenant_id')
                || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            // vacation_years tiene UNIQUE(tenant_id, user_id, year): con tenant_id NULL
            // se colaron duplicados (doble envío). Se asigna fila a fila y los
            // duplicados se dejan en NULL (ocultos por el scope) sin borrar datos.
            if ($table === 'vacation_years') {
                $this->backfillVacationYears($mainTenantId);

                continue;
            }

            DB::table($table)
                ->whereNull('tenant_id')
                ->update([
                    'tenant_id' => DB::raw(
                        "(SELECT users.tenant_id FROM users WHERE users.id = {$table}.user_id)"
                    ),
                ]);

            if ($mainTenantId) {
                DB::table($table)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $mainTenantId]);
            }
        }
    }

    private function backfillVacationYears(?int $mainTenantId): void
    {
        $skipped = [];

        // Primero las filas con más días usados y más recientes: son las que se conservan.
        $rows = DB::table('vacation_years')
            ->leftJoin('users', 'users.id', '=', 'vacation_years.user_id')
            ->whereNull('vacation_years.tenant_id')
            ->orderByDesc('vacation_years.used_days')
            ->orderByDesc('vacation_years.id')
            ->get(['vacation_years.id', 'vacation_years.user_id', 'vacation_years.year', 'users.tenant_id as user_tenant_id']);

        foreach ($rows as $row) {
            $tenantId = $row->user_tenant_id ?? $mainTenantId;

            if (! $tenantId) {
                continue;
            }

            $exists = DB::table('vacation_years')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $row->user_id)
                ->where('year', $row->year)
                ->exists();

            if ($exists) {
                $skipped[] = $row->id;

                continue;
            }

            DB::table('vacation_years')->where('id', $row->id)->update(['tenant_id' => $tenantId]);
        }

        if ($skipped !== []) {
            $message = 'vacation_years duplicados dejados con tenant_id NULL (revisar manualmente): ids '.implode(', ', $skipped);
            logger()->warning($message);

            if (app()->runningInConsole()) {
                fwrite(STDERR, "  ⚠ {$message}\n");
            }
        }
    }

    public function down(): void
    {
        // Backfill de datos: no se revierte.
    }
};
