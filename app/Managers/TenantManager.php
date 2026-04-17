<?php

namespace App\Managers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantManager
{
    protected ?int $tenantId = null;

    protected ?Tenant $tenant = null;

    /**
     * Obtener el tenant actual (por ID)
     */
    public function getTenantId(): ?int
    {
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        // 1. Verificar si hay un tenant en sesión (request actual)
        if ($tenantId = $this->resolveFromRequest()) {
            return $this->tenantId = $tenantId;
        }

        // 2. Verificar si hay un tenant por defecto (main tenant)
        if ($mainTenant = $this->getMainTenant()) {
            return $this->tenantId = $mainTenant->id;
        }

        return null;
    }

    /**
     * Obtener el tenant completo
     */
    public function getTenant(): ?Tenant
    {
        if ($this->tenant) {
            return $this->tenant;
        }

        $tenantId = $this->getTenantId();

        if ($tenantId) {
            $this->tenant = Tenant::find($tenantId);
        }

        return $this->tenant;
    }

    /**
     * Establecer el tenant actual manualmente
     */
    public function setTenant(Tenant|int|null $tenant): void
    {
        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;
            $this->tenant = $tenant;
        } elseif (is_int($tenant)) {
            $this->tenantId = $tenant;
            $this->tenant = null; // Se cargará lazily
        } else {
            $this->tenantId = null;
            $this->tenant = null;
        }
    }

    /**
     * Resetear el tenant (para testing o when no tenant context)
     */
    public function reset(): void
    {
        $this->tenantId = null;
        $this->tenant = null;
    }

    /**
     * Verificar si estamos en contexto de tenant
     */
    public function hasTenant(): bool
    {
        return $this->getTenantId() !== null;
    }

    /**
     * Verificar si el tenant actual es el main tenant
     */
    public function isMainTenant(): bool
    {
        $tenant = $this->getTenant();

        return $tenant && $tenant->is_main;
    }

    /**
     * Obtener el tenant principal (main)
     */
    public function getMainTenant(): ?Tenant
    {
        return Cache::remember('main_tenant', 3600, function () {
            return Tenant::main()->active()->first();
        });
    }

    /**
     * Resolver tenant desde el request actual (subdomain o header)
     */
    public function resolveFromRequest(): ?int
    {
        // 1. Por subdomain: empresa.app.com
        if ($subdomain = $this->resolveSubdomain()) {
            $tenant = Tenant::where('slug', $subdomain)
                ->orWhere('domain', request()->getHost())
                ->active()
                ->first();

            if ($tenant) {
                return $tenant->id;
            }
        }

        // 2. Por header X-Tenant-ID (API)
        if ($tenantId = request()->header('X-Tenant-ID')) {
            $tenant = Tenant::where('id', $tenantId)->active()->first();

            if ($tenant) {
                return $tenant->id;
            }
        }

        // 3. Por sesión
        if ($tenantId = session('tenant_id')) {
            return (int) $tenantId;
        }

        return null;
    }

    /**
     * Resolver subdomain desde el host actual
     */
    protected function resolveSubdomain(): ?string
    {
        $host = request()->getHost();
        $appDomain = config('app.domain', 'localhost');

        // Si es localhost, no hay subdomain
        if ($host === $appDomain || $host === 'localhost' || $host === '127.0.0.1') {
            return null;
        }

        // Extraer subdomain: empresa.dominio.com -> empresa
        if (str_contains($host, $appDomain)) {
            return str_replace('.'.$appDomain, '', $host);
        }

        return null;
    }

    /**
     * Buscar tenant por dominio personalizado
     */
    public static function resolveByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)
            ->orWhere(function ($query) use ($domain) {
                $query->where('domain', 'like', "%{$domain}");
            })
            ->active()
            ->first();
    }
}
