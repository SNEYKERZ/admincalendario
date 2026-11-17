<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Lista todas las áreas para dropdowns.
     * GET /api/areas-list
     */
    public function list(Request $request): JsonResponse
    {
        $areas = Area::query()
            ->ordered()
            ->get(['id', 'name', 'color']);

        return response()->json($areas);
    }

    /**
     * Lista todas las áreas.
     * GET /api/areas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Area::withCount('users');

        // Filtrar por estado
        if ($request->has('active') && $request->active !== 'all') {
            $query->where('is_active', $request->active === 'active');
        }

        // Admins ven todas las áreas del tenant (Tenantable scope automático)
        // SuperAdmins ven todas las áreas de todos los tenants
        $areas = $query->ordered()->get();

        return response()->json([
            'areas' => $areas->map(function ($area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'description' => $area->description,
                    'color' => $area->color,
                    'display_order' => $area->display_order,
                    'is_active' => $area->is_active,
                    'employee_count' => $area->users_count,
                    'created_at' => $area->created_at,
                ];
            }),
        ]);
    }

    /**
     * Crea una nueva área.
     * POST /api/areas
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', TenantRule::unique('areas', 'name')],
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'leader_ids' => 'nullable|array',
            'leader_ids.*' => ['integer', TenantRule::exists('users')],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['color'] = $validated['color'] ?? '#3B82F6';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $leaderIds = $validated['leader_ids'] ?? [];
        unset($validated['leader_ids']);

        $area = Area::create($validated);

        if (! empty($leaderIds)) {
            $area->leaders()->attach($leaderIds);
            $this->promoteLeadersToAdmin($leaderIds);
        }

        $area->load('leaders');

        return response()->json([
            'success' => true,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'color' => $area->color,
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
                'leader_ids' => $area->leaders->pluck('id')->toArray(),
            ],
            'message' => 'Área creada exitosamente',
        ], 201);
    }

    /**
     * Muestra una área específica.
     * GET /api/areas/{area}
     */
    public function show(Area $area): JsonResponse
    {
        $area->loadCount('users');
        $area->load(['users' => function ($query) {
            $query->select('id', 'name', 'email', 'is_active');
        }, 'leaders' => function ($query) {
            $query->select('id', 'name', 'email');
        }]);

        return response()->json([
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'color' => $area->color,
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
                'employee_count' => $area->users_count,
                'employees' => $area->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                    ];
                }),
                'leader_ids' => $area->leaders->pluck('id')->toArray(),
                'leaders' => $area->leaders->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }),
                'created_at' => $area->created_at,
            ],
        ]);
    }

    /**
     * Actualiza un área.
     * PUT /api/areas/{area}
     */
    public function update(Request $request, Area $area): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', TenantRule::unique('areas', 'name', $area->id)],
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'leader_ids' => 'nullable|array',
            'leader_ids.*' => ['integer', TenantRule::exists('users')],
        ]);

        $leaderIds = $validated['leader_ids'] ?? null;
        unset($validated['leader_ids']);

        $area->update($validated);

        if ($leaderIds !== null) {
            $currentLeaderIds = $area->leaders()->get()->pluck('id')->toArray();
            $removedLeaderIds = array_diff($currentLeaderIds, $leaderIds);

            if (in_array($request->user()->id, $removedLeaderIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes quitarte a ti mismo como líder de esta área. Pídele a otro administrador que lo haga.',
                ], 422);
            }

            $area->leaders()->sync($leaderIds);
            $this->promoteLeadersToAdmin($leaderIds);
        }

        $area->load('leaders');

        return response()->json([
            'success' => true,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'color' => $area->color,
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
                'leader_ids' => $area->leaders->pluck('id')->toArray(),
            ],
            'message' => 'Área actualizada exitosamente',
        ]);
    }

    /**
     * Elimina un área.
     * DELETE /api/areas/{area}
     */
    public function destroy(Area $area): JsonResponse
    {
        // Verificar si hay usuarios asociados
        if ($area->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el área porque tiene empleados asociados.Primero reassigna los empleados.',
            ], 422);
        }

        $area->delete();

        return response()->json([
            'success' => true,
            'message' => 'Área eliminada exitosamente',
        ]);
    }

    /**
     * Promueve a "admin" a los usuarios asignados como líderes de área,
     * ya que el módulo de áreas solo es visible para administradores.
     */
    protected function promoteLeadersToAdmin(array $leaderIds): void
    {
        // User aplica TenantScope: solo se promueven usuarios del tenant actual.
        User::whereIn('id', $leaderIds)
            ->where('role', UserRole::COLLABORATOR->value)
            ->update(['role' => UserRole::ADMIN->value]);
    }

    /**
     * Obtiene métricas por área.
     * GET /api/areas/metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $areas = Area::withCount('users')->ordered()->get();

        // Antes usaba columnas/estados inexistentes ('start', 'approved') y
        // una consulta por área. Ahora: una sola consulta agrupada.
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $absencesByArea = \App\Models\Absence::query()
            ->join('users', 'users.id', '=', 'absences.user_id')
            ->where('absences.status', \App\Enums\AbsenceStatus::APPROVED->value)
            ->whereBetween('absences.start_datetime', [$monthStart, $monthEnd])
            ->whereIn('users.area_id', $areas->pluck('id'))
            ->groupBy('users.area_id')
            ->selectRaw('users.area_id as area_id, COUNT(DISTINCT absences.user_id) as total')
            ->pluck('total', 'area_id');

        $metrics = $areas->map(function ($area) use ($absencesByArea) {
            $absencesThisMonth = (int) ($absencesByArea[$area->id] ?? 0);

            return [
                'id' => $area->id,
                'name' => $area->name,
                'color' => $area->color,
                'total_employees' => $area->users_count,
                'absences_this_month' => $absencesThisMonth,
                'availability_percentage' => $area->users_count > 0
                    ? round((($area->users_count - $absencesThisMonth) / $area->users_count) * 100, 1)
                    : 100,
            ];
        });

        return response()->json([
            'metrics' => $metrics,
            'summary' => [
                'total_areas' => $areas->count(),
                'total_employees' => $areas->sum('users_count'),
            ],
        ]);
    }
}
