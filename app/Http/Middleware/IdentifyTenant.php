<?php

namespace App\Http\Middleware;

use App\Managers\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * Este middleware identifica el tenant actual basándose en:
     * 1. Subdomain (empresa.app.com)
     * 2. Dominio personalizado
     * 3. Header X-Tenant-ID (API)
     * 4. Sesión
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantManager = app(TenantManager::class);

        // No aplicar para rutas públicas (login, register, etc)
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        // Manejo especial para SuperAdmin
        if (auth()->check() && auth()->user()->isSuperAdmin()) {
            $context = session('super_admin_context');

            // Si está en contexto global (no impersonando), NO establecer tenant
            if (!$context || !$context['impersonated_user_id']) {
                // SuperAdmin en contexto global - permitir que continúe sin tenant
                return $next($request);
            }

            // Si está impersonando, establecer el tenant del impersonation
            if ($context['tenant_id']) {
                $tenantManager->setTenant($context['tenant_id']);
            }

            return $next($request);
        }

        // Lógica normal para Admin/Colaborador
        $tenantManager->resolveFromRequest();

        // Si no hay tenant y no es ruta pública, redirigir o mostrar error
        if (! $tenantManager->hasTenant()) {
            // Para APIs, devolver error 401
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Tenant no identificado',
                    'message' => 'No se pudo identificar el tenant para esta solicitud',
                ], 401);
            }

            // Para web, podrías redirigir a una página de selección de tenant
            // O simplemente usar el tenant principal por defecto
            $mainTenant = $tenantManager->getMainTenant();
            if ($mainTenant) {
                $tenantManager->setTenant($mainTenant);
            }
        }

        return $next($request);
    }

    /**
     * Determina si la ruta es pública (no requiere tenant)
     */
    protected function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'login',
            'logout',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'two-factor.login',
            'api/public',
            'api/auth',
        ];

        $routeName = $request->route()?->getName();
        $path = $request->path();

        foreach ($publicRoutes as $route) {
            if ($routeName && str_contains($routeName, $route)) {
                return true;
            }
            if (str_starts_with($path, $route)) {
                return true;
            }
        }

        return false;
    }
}
