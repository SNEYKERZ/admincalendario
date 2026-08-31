<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Absence;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Validar que sea SuperAdmin en contexto global
        if (session('super_admin_context.impersonated_user_id')) {
            return redirect('/dashboard');
        }

        // Obtener estadísticas de plataforma
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::active()->count(),
            'inactive_tenants' => Tenant::where('is_active', false)->count(),
            'total_users' => User::withoutGlobalScopes()->count(),
            'total_admins' => User::withoutGlobalScopes()->admins()->count(),
            'total_absences' => Absence::withoutGlobalScopes()->count(),
            'pending_absences' => Absence::withoutGlobalScopes()->where('status', 'pendiente')->count(),
        ];

        // Obtener últimos tenants
        $recentTenants = Tenant::with('users')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($tenant) {
                $admin = $tenant->users()
                    ->where('role', 'admin')
                    ->first();

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'domain' => $tenant->domain,
                    'is_active' => $tenant->is_active,
                    'created_at' => $tenant->created_at->format('Y-m-d H:i'),
                    'admin_name' => $admin?->name ?? 'Sin admin',
                ];
            });

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
        ]);
    }
}
