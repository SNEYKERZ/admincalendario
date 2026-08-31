<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SuperAdminAudit;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, $userId)
    {
        // Validar que sea SuperAdmin
        if (!auth()->user()?->isSuperAdmin()) {
            return redirect('/dashboard')->with('error', 'Acceso denegado');
        }

        // No puede estar impersonando ya
        if (session('super_admin_context.impersonated_user_id')) {
            return redirect()->back()->with('error', 'Ya estás impersonando a alguien. Sal primero.');
        }

        // Obtener usuario a impersonar
        $impersonated = User::withoutGlobalScopes()->find($userId);

        // Validaciones
        if (!$impersonated) {
            return redirect()->back()->with('error', 'Usuario no encontrado');
        }

        if (!$impersonated->isAdmin()) {
            return redirect()->back()->with('error', 'Solo puedes impersonar a administradores');
        }

        if (!$impersonated->tenant_id) {
            return redirect()->back()->with('error', 'El usuario no pertenece a ningún tenant');
        }

        // Verificar que el tenant esté activo
        $tenant = $impersonated->tenant()->first();
        if (!$tenant || !$tenant->is_active) {
            return redirect()->back()->with('error', 'El tenant no está activo');
        }

        // Establecer contexto de impersonation
        session([
            'super_admin_context' => [
                'real_user_id' => auth()->id(),
                'impersonated_user_id' => $impersonated->id,
                'impersonated_name' => $impersonated->name,
                'impersonated_email' => $impersonated->email,
                'tenant_id' => $impersonated->tenant_id,
                'effective_role' => 'admin',
                'started_at' => now()->toDateTimeString(),
            ]
        ]);

        // Registrar en auditoría
        SuperAdminAudit::log(
            auth()->user(),
            'start_impersonation',
            "Comenzó a impersonar a {$impersonated->name} ({$impersonated->email})",
            $impersonated,
            $tenant,
            null,
            $request->ip()
        );

        return redirect('/dashboard')->with('success', "Visualizando como: {$impersonated->name}");
    }

    public function stop(Request $request)
    {
        // Validar que sea SuperAdmin
        if (!auth()->user()?->isSuperAdmin()) {
            return redirect('/dashboard')->with('error', 'Acceso denegado');
        }

        // Obtener contexto actual
        $context = session('super_admin_context');

        if (!$context || !$context['impersonated_user_id']) {
            return redirect('/superadmin/dashboard')->with('error', 'No estás impersonando a nadie');
        }

        // Obtener datos para auditoría
        $impersonated = User::withoutGlobalScopes()->find($context['impersonated_user_id']);
        $tenant = $impersonated?->tenant()->first();

        // Registrar en auditoría
        if ($impersonated && $tenant) {
            SuperAdminAudit::log(
                auth()->user(),
                'end_impersonation',
                "Terminó impersonación de {$impersonated->name}",
                $impersonated,
                $tenant,
                null,
                $request->ip()
            );
        }

        // Limpiar contexto
        session()->forget('super_admin_context');

        // Resetear contexto a global
        session([
            'super_admin_context' => [
                'real_user_id' => auth()->id(),
                'impersonated_user_id' => null,
                'tenant_id' => null,
                'effective_role' => 'superadmin',
                'started_at' => now()->toDateTimeString(),
            ]
        ]);

        return redirect('/superadmin/dashboard')->with('success', 'Impersonación finalizada');
    }
}
