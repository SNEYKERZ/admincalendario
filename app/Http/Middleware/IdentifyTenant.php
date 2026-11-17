<?php

namespace App\Http\Middleware;

use App\Managers\TenantManager;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Fija el tenant de la petición.
     *
     * - Usuario autenticado: SIEMPRE su propio tenant (users.tenant_id). Para
     *   el superadmin, el tenant del contexto de impersonación o ninguno
     *   (contexto global).
     * - Invitado: subdominio / dominio propio, si existe.
     *
     * Además corta la sesión de usuarios desactivados o de tenants inactivos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantManager = app(TenantManager::class);

        // Cada petición empieza sin tenant fijado (el singleton puede
        // sobrevivir entre peticiones en Octane/tests).
        $tenantManager->reset();

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            if ($tenantId = $tenantManager->resolveFromRequest()) {
                $tenantManager->setTenant($tenantId);
            }

            return $next($request);
        }

        if ($blocked = $this->blockIfDisabled($request, $user)) {
            return $blocked;
        }

        $tenantManager->setTenant($tenantManager->tenantIdForUser($user));

        return $next($request);
    }

    /**
     * Cierra la sesión si el usuario fue desactivado o su empresa está inactiva.
     */
    protected function blockIfDisabled(Request $request, User $user): ?Response
    {
        $reason = null;

        if ($user->is_active === false) {
            $reason = 'Tu usuario está desactivado. Contacta al administrador.';
        } elseif (! $user->isSuperAdmin() && $user->tenant_id) {
            $tenant = Tenant::query()->find($user->tenant_id);

            if ($tenant && ! $tenant->is_active) {
                $reason = 'La empresa asociada a tu cuenta está inactiva.';
            }
        }

        if ($reason === null) {
            return null;
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $reason], 403);
        }

        return redirect('/login')->withErrors(['email' => $reason]);
    }
}
