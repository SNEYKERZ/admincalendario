<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ModuleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateModuleAccess
{
    public function __construct(protected ModuleAccessService $moduleAccessService)
    {
    }

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        // Si hay impersonación de SuperAdmin, validar contra el usuario impersonado
        $superAdminContext = session('super_admin_context');
        if ($superAdminContext && $superAdminContext['impersonated_user_id']) {
            $impersonatedUser = User::withoutGlobalScopes()->find($superAdminContext['impersonated_user_id']);
            $user = $impersonatedUser ?: $user;
        }

        if (!$user || !$this->moduleAccessService->userCanAccessModule($user, $module)) {
            abort(403, 'Module not available in your plan');
        }

        return $next($request);
    }
}
