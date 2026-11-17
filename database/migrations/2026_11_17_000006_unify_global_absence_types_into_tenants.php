<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Unifica los tipos de ausencia: las ausencias de cada empresa que todavía
 * apuntan a un tipo GLOBAL (tenant_id NULL) pasan al tipo equivalente de la
 * propia empresa (mismo nombre, sin distinguir mayúsculas ni tildes).
 *
 * - Si la empresa no tiene el equivalente, se crea una copia del global con
 *   las mismas reglas y se usa esa.
 * - Si el equivalente tiene otra regla de saldo (deducts_vacation), NO se
 *   mueve nada: al rechazar o borrar una ausencia aprobada el sistema devuelve
 *   días según el tipo actual, así que cambiar la regla alteraría saldos. Se
 *   informa para decidirlo a mano.
 * - Los tipos globales se conservan como plantilla del sistema.
 *
 * Cada movimiento queda en el log (ids de ausencias) para poder auditarlo.
 * Idempotente: una segunda ejecución no encuentra nada que mover.
 */
return new class extends Migration
{
    /** Reglas que, si difieren, impiden mover ausencias de forma segura. */
    private const BALANCE_RULES = ['deducts_vacation'];

    /** Columnas que se copian al crear el equivalente de una empresa. */
    private const COPY_COLUMNS = [
        'name', 'deducts_vacation', 'requires_approval', 'counts_as_hours',
        'default_include_saturday', 'default_include_sunday', 'default_include_holidays',
        'color', 'max_days_limit',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('absence_types') || ! Schema::hasColumn('absence_types', 'tenant_id')) {
            return;
        }

        $moved = [];
        $created = [];
        $skipped = [];

        DB::transaction(function () use (&$moved, &$created, &$skipped) {
            $globals = DB::table('absence_types')->whereNull('tenant_id')->orderBy('id')->get();

            foreach ($globals as $global) {
                $tenantIds = DB::table('absences')
                    ->where('absence_type_id', $global->id)
                    ->whereNotNull('tenant_id')
                    ->distinct()
                    ->pluck('tenant_id');

                foreach ($tenantIds as $tenantId) {
                    $local = $this->findEquivalent($global, (int) $tenantId);

                    if (! $local) {
                        $localId = $this->cloneForTenant($global, (int) $tenantId);
                        $created[] = "{$global->name} (empresa {$tenantId}, nuevo tipo {$localId})";
                    } elseif ($differences = $this->balanceDifferences($global, $local)) {
                        $count = DB::table('absences')
                            ->where('absence_type_id', $global->id)
                            ->where('tenant_id', $tenantId)
                            ->count();
                        $skipped[] = "{$global->name} (empresa {$tenantId}: {$count} ausencias, global {$global->id} → empresa {$local->id}, difiere ".implode(', ', $differences).')';

                        continue;
                    } else {
                        $localId = $local->id;
                    }

                    $absenceIds = DB::table('absences')
                        ->where('absence_type_id', $global->id)
                        ->where('tenant_id', $tenantId)
                        ->pluck('id')
                        ->all();

                    DB::table('absences')->whereIn('id', $absenceIds)->update(['absence_type_id' => $localId]);

                    $moved[] = "{$global->name}: tipo {$global->id} → {$localId} (empresa {$tenantId}), ausencias [".implode(',', $absenceIds).']';
                }
            }
        });

        $this->report('Ausencias movidas a tipos de empresa', $moved, 'info');
        $this->report('Tipos de empresa creados a partir de globales', $created, 'info');
        $this->report('NO unificados (la regla de saldo difiere, decidir a mano)', $skipped, 'warning');
    }

    public function down(): void
    {
        // Irreversible de forma automática: los ids movidos quedan en el log.
    }

    private function findEquivalent(object $global, int $tenantId): ?object
    {
        $key = $this->normalize($global->name);

        return DB::table('absence_types')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get()
            ->first(fn ($type) => $this->normalize($type->name) === $key);
    }

    private function cloneForTenant(object $global, int $tenantId): int
    {
        $data = ['tenant_id' => $tenantId, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];

        foreach (self::COPY_COLUMNS as $column) {
            if (property_exists($global, $column)) {
                $data[$column] = $global->{$column};
            }
        }

        return (int) DB::table('absence_types')->insertGetId($data);
    }

    /** @return list<string> */
    private function balanceDifferences(object $global, object $local): array
    {
        return array_values(array_filter(
            self::BALANCE_RULES,
            fn (string $rule) => (bool) ($global->{$rule} ?? false) !== (bool) ($local->{$rule} ?? false),
        ));
    }

    private function normalize(string $name): string
    {
        return Str::lower(trim(Str::ascii($name)));
    }

    /** @param list<string> $lines */
    private function report(string $title, array $lines, string $level): void
    {
        if ($lines === []) {
            return;
        }

        $message = $title.': '.implode(' | ', $lines);
        logger()->{$level}($message);

        if (app()->runningInConsole()) {
            fwrite(STDERR, '  '.($level === 'warning' ? '⚠' : '•')." {$message}\n");
        }
    }
};
