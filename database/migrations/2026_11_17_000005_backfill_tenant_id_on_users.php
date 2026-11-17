<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asigna el tenant principal a los usuarios (no superadmin) sin tenant_id.
 *
 * En tiempo de ejecución esos usuarios ya se trataban como del tenant
 * principal (TenantManager::tenantIdForUser), pero al tener tenant_id NULL no
 * aparecían en los listados de usuarios de su empresa. Los superadmins siguen
 * sin tenant (contexto global). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('users', 'tenant_id')) {
            return;
        }

        $mainTenantId = DB::table('tenants')->where('is_main', true)->orderBy('id')->value('id');

        if (! $mainTenantId) {
            return;
        }

        $skipped = [];

        $legacyUsers = DB::table('users')
            ->whereNull('tenant_id')
            ->where('role', '!=', 'superadmin')
            ->orderBy('id')
            ->get(['id', 'email']);

        foreach ($legacyUsers as $user) {
            // UNIQUE(tenant_id, email): si ya existe la misma cuenta en el tenant
            // principal, la legada es un duplicado y se deja tal cual.
            $duplicate = DB::table('users')
                ->where('tenant_id', $mainTenantId)
                ->where('email', $user->email)
                ->exists();

            if ($duplicate) {
                $skipped[] = "{$user->id} ({$user->email})";

                continue;
            }

            DB::table('users')->where('id', $user->id)->update(['tenant_id' => $mainTenantId]);
        }

        if ($skipped !== []) {
            $message = 'Usuarios legados duplicados (mismo email ya existe en el tenant principal), se dejan sin tenant: '.implode(', ', $skipped);
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
