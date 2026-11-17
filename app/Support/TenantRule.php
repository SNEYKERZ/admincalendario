<?php

namespace App\Support;

use App\Managers\TenantManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Reglas de validación acotadas al tenant actual.
 *
 * `exists:users,id` / `unique:areas,name` consultan la tabla completa (sin el
 * TenantScope de Eloquent), lo que permitía referenciar registros de otra
 * empresa o chocar con nombres de otra empresa.
 */
class TenantRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        $rule = Rule::exists($table, $column);
        $tenantId = app(TenantManager::class)->getTenantId();

        if ($tenantId) {
            $rule->where('tenant_id', $tenantId);
        }

        return $rule;
    }

    public static function unique(string $table, string $column, mixed $ignoreId = null): Unique
    {
        $rule = Rule::unique($table, $column);
        $tenantId = app(TenantManager::class)->getTenantId();

        if ($tenantId) {
            $rule->where('tenant_id', $tenantId);
        }

        if ($ignoreId !== null) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }
}
