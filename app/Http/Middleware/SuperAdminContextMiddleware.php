<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplica a SuperAdmin
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            return $next($request);
        }

        // Obtener o crear contexto de impersonation
        $context = session('super_admin_context');

        if (!$context) {
            // Contexto global por defecto
            session(['super_admin_context' => [
                'real_user_id' => auth()->id(),
                'impersonated_user_id' => null,
                'tenant_id' => null,
                'effective_role' => 'superadmin',
                'started_at' => now()->toDateTimeString(),
            ]]);

            return $next($request);
        }

        // Validar que impersonation sea válida (si existe)
        if ($context['impersonated_user_id']) {
            $impersonated = User::withoutGlobalScopes()
                ->find($context['impersonated_user_id']);

            // Verificar que el usuario impersonado existe y es admin del tenant correcto
            if (!$impersonated ||
                !$impersonated->isAdmin() ||
                $impersonated->tenant_id !== $context['tenant_id']) {

                // Impersonation inválida, limpiar contexto
                session()->forget('super_admin_context');

                return redirect('/superadmin/dashboard')
                    ->with('error', 'La impersonación expiró o es inválida. Intenta de nuevo.');
            }
        }

        // Disponibilizar el contexto en el request
        $request->attributes->set('super_admin_context', $context);

        return $next($request);
    }
}
