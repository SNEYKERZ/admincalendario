<?php

namespace App\Managers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TenantManager
{
    protected ?int $tenantId = null;

    protected ?Tenant $tenant = null;

    /**
     * true cuando el tenant fue fijado explícitamente (middleware, jobs, tests).
     */
    protected bool $explicit = false;

    /**
     * Evita recursión: resolver el usuario autenticado dispara una consulta a
     * `users`, que a su vez aplica el TenantScope y vuelve a pedir el tenant.
     */
    protected bool $resolving = false;

    /**
     * Obtener el tenant actual (por ID).
     *
     * Orden de resolución:
     *  1. Tenant fijado explícitamente con setTenant().
     *  2. Usuario autenticado: su propio tenant (o el del contexto de
     *     impersonación si es superadmin). Nunca se acepta un tenant elegido
     *     por el cliente (cabecera/sesión) para un usuario autenticado.
     *  3. Sin usuario autenticado: null (sin filtro). Los flujos públicos que
     *     necesiten tenant deben llamar a resolveFromRequest() explícitamente.
     */
    public function getTenantId(): ?int
    {
        if ($this->explicit) {
            return $this->tenantId;
        }

        if ($this->resolving) {
            return null;
        }

        $this->resolving = true;

        try {
            $user = Auth::user();
        } finally {
            $this->resolving = false;
        }

        if (! $user instanceof User) {
            return null;
        }

        return $this->tenantIdForUser($user);
    }

    /**
     * Determina el tenant efectivo de un usuario autenticado.
     */
    public function tenantIdForUser(User $user): ?int
    {
        if ($user->isSuperAdmin()) {
            $context = session('super_admin_context');

            if (is_array($context) && ! empty($context['impersonated_user_id']) && ! empty($context['tenant_id'])) {
                return (int) $context['tenant_id'];
            }

            // SuperAdmin en contexto global: sin filtro de tenant.
            return null;
        }

        if ($user->tenant_id) {
            return (int) $user->tenant_id;
        }

        // Usuarios legados sin tenant asignado: se confinan al tenant principal
        // (comportamiento histórico de instalaciones mono-empresa).
        return $this->getMainTenant()?->id;
    }

    /**
     * Obtener el tenant completo
     */
    public function getTenant(): ?Tenant
    {
        $tenantId = $this->getTenantId();

        if (! $tenantId) {
            return null;
        }

        if ($this->tenant && $this->tenant->id === $tenantId) {
            return $this->tenant;
        }

        return $this->tenant = Tenant::find($tenantId);
    }

    /**
     * Establecer el tenant actual manualmente
     */
    public function setTenant(Tenant|int|null $tenant): void
    {
        $this->explicit = true;

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
     * Resetear el tenant (vuelve a la resolución automática).
     */
    public function reset(): void
    {
        $this->tenantId = null;
        $this->tenant = null;
        $this->explicit = false;
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
     * Resolver tenant desde el request actual (subdominio o dominio propio).
     *
     * Solo se usa para peticiones SIN usuario autenticado. La cabecera
     * X-Tenant-ID y la sesión ya no se aceptan: permitían a cualquier cliente
     * elegir el tenant de los datos que consulta.
     */
    public function resolveFromRequest(): ?int
    {
        if ($subdomain = $this->resolveSubdomain()) {
            $tenant = Tenant::query()
                ->where(function ($query) use ($subdomain) {
                    $query->where('slug', $subdomain)
                        ->orWhere('domain', request()->getHost());
                })
                ->active()
                ->first();

            if ($tenant) {
                return $tenant->id;
            }
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
        if (str_ends_with($host, '.'.$appDomain)) {
            return substr($host, 0, -strlen('.'.$appDomain));
        }

        return null;
    }

    /**
     * Buscar tenant por dominio personalizado
     */
    public static function resolveByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)
            ->active()
            ->first();
    }
}
