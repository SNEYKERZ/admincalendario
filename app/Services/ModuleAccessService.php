<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class ModuleAccessService
{
    public function userCanAccessModule(User $user, string $moduleSlug): bool
    {
        // SuperAdmin global (sin tenant) no puede acceder a módulos normales
        if ($user->isSuperAdmin() && !$user->tenant_id) {
            return false;
        }

        // User debe ser admin y tener tenant
        if (!$user->isAdmin() || !$user->tenant_id) {
            return false;
        }

        // Verificar que el módulo existe
        $module = $this->getModule($moduleSlug);
        if (!$module) {
            return false;
        }

        // Módulos core siempre disponibles
        if ($module->is_core) {
            return true;
        }

        // Verificar que el módulo está en el plan del tenant
        $tenant = $user->tenant;
        return $tenant && $tenant->hasModule($moduleSlug);
    }

    public function getTenantEnabledModules(Tenant $tenant): Collection
    {
        return $tenant->getCurrentPlanModules();
    }

    public function getTenantEnabledModuleSlugs(Tenant $tenant): array
    {
        return $tenant->getEnabledModules();
    }

    public function getModule(string $moduleSlug): ?Module
    {
        return Module::where('slug', $moduleSlug)->first();
    }

    public function getAllModules(): Collection
    {
        return Module::ordered()->get();
    }

    public function getCoreModules(): Collection
    {
        return Module::core()->ordered()->get();
    }

    public function getOptionalModules(): Collection
    {
        return Module::notCore()->ordered()->get();
    }
}
