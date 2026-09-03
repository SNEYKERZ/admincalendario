<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        // Validar que sea SuperAdmin
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403);
        }

        // Construir query
        $query = SuperAdminAudit::with(['superadmin', 'impersonatedUser', 'tenant']);

        // Filtros
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('tenant_id') && $request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->has('superadmin_id') && $request->superadmin_id) {
            $query->where('superadmin_id', $request->superadmin_id);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->where('created_at', '>=', $request->from_date . ' 00:00:00');
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        // Ordenar y paginar
        $paginated = $query->orderBy('created_at', 'desc')->paginate(50);

        return Inertia::render('SuperAdmin/Audit', [
            'data' => $paginated->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'superadmin_name' => $log->superadmin?->name,
                'superadmin_email' => $log->superadmin?->email,
                'impersonated_name' => $log->impersonatedUser?->name,
                'impersonated_email' => $log->impersonatedUser?->email,
                'tenant_name' => $log->tenant?->name,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ])->toArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'filters' => [
                'action' => $request->action,
                'tenant_id' => $request->tenant_id,
                'superadmin_id' => $request->superadmin_id,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],
        ]);
    }
}
