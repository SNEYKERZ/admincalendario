<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\SuperAdminAudit;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index()
    {
        // Validar que sea SuperAdmin en contexto global
        if (session('super_admin_context.impersonated_user_id')) {
            return redirect('/dashboard');
        }

        $tenants = Tenant::with('users')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tenant) {
                $admin = $tenant->users()
                    ->where('role', 'admin')
                    ->first();

                $userCount = $tenant->users()->count();
                $absenceCount = $tenant->absences()->count();

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'domain' => $tenant->domain ?? $tenant->slug . '.ausentra.com',
                    'is_active' => $tenant->is_active,
                    'is_main' => $tenant->is_main,
                    'user_count' => $userCount,
                    'absence_count' => $absenceCount,
                    'admin_id' => $admin?->id,
                    'admin_name' => $admin?->name ?? 'Sin admin',
                    'admin_email' => $admin?->email,
                    'created_at' => $tenant->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('SuperAdmin/Tenants', [
            'tenants' => $tenants,
        ]);
    }

    public function show(Tenant $tenant)
    {
        // Validar que sea SuperAdmin en contexto global
        if (session('super_admin_context.impersonated_user_id')) {
            return redirect('/dashboard');
        }

        $admin = $tenant->users()
            ->where('role', 'admin')
            ->first();

        $stats = [
            'total_users' => $tenant->users()->count(),
            'admin_count' => $tenant->users()->where('role', 'admin')->count(),
            'collaborator_count' => $tenant->users()->where('role', 'colaborador')->count(),
            'total_absences' => $tenant->absences()->count(),
            'pending_absences' => $tenant->absences()->where('status', 'pendiente')->count(),
            'approved_absences' => $tenant->absences()->where('status', 'aprobado')->count(),
            'areas_count' => $tenant->areas()->count(),
        ];

        // Últimas acciones de SuperAdmin en este tenant
        $auditLog = SuperAdminAudit::where('tenant_id', $tenant->id)
            ->with(['superadmin', 'impersonatedUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'superadmin_name' => $log->superadmin?->name,
                'impersonated_name' => $log->impersonatedUser?->name,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('SuperAdmin/Tenants/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'is_active' => $tenant->is_active,
                'is_main' => $tenant->is_main,
                'timezone' => $tenant->timezone,
                'locale' => $tenant->locale,
                'created_at' => $tenant->created_at->format('Y-m-d H:i'),
                'admin_name' => $admin?->name,
                'admin_email' => $admin?->email,
                'admin_id' => $admin?->id,
            ],
            'stats' => $stats,
            'auditLog' => $auditLog,
        ]);
    }

    public function activate(Tenant $tenant)
    {
        // Validar contexto
        if (session('super_admin_context.impersonated_user_id')) {
            return response()->json(['error' => 'No puedes activar tenants mientras estás impersonando'], 403);
        }

        $tenant->update(['is_active' => true]);

        SuperAdminAudit::log(
            auth()->user(),
            'activate_tenant',
            "Tenant '{$tenant->name}' activado",
            null,
            $tenant
        );

        return redirect()->back()->with('success', "Tenant '{$tenant->name}' activado correctamente");
    }

    public function deactivate(Tenant $tenant)
    {
        // Validar contexto
        if (session('super_admin_context.impersonated_user_id')) {
            return response()->json(['error' => 'No puedes desactivar tenants mientras estás impersonando'], 403);
        }

        // No desactivar el tenant principal
        if ($tenant->is_main) {
            return redirect()->back()->with('error', 'No puedes desactivar el tenant principal');
        }

        $tenant->update(['is_active' => false]);

        SuperAdminAudit::log(
            auth()->user(),
            'deactivate_tenant',
            "Tenant '{$tenant->name}' desactivado",
            null,
            $tenant
        );

        return redirect()->back()->with('success', "Tenant '{$tenant->name}' desactivado correctamente");
    }

    public function suspend(Tenant $tenant)
    {
        // Validar contexto
        if (session('super_admin_context.impersonated_user_id')) {
            return response()->json(['error' => 'No puedes suspender tenants mientras estás impersonando'], 403);
        }

        // No suspender el tenant principal
        if ($tenant->is_main) {
            return redirect()->back()->with('error', 'No puedes suspender el tenant principal');
        }

        $tenant->update(['is_active' => false]);

        SuperAdminAudit::log(
            auth()->user(),
            'suspend_tenant',
            "Tenant '{$tenant->name}' suspendido",
            null,
            $tenant
        );

        return redirect()->back()->with('success', "Tenant '{$tenant->name}' suspendido");
    }
}
