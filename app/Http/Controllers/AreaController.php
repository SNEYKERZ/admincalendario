<?php

namespace App\Http\Controllers;

use App\Models\Area;
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
        $query = Area::query();

        if (! $request->user()->isSuperAdmin()) {
            $query->byCreator($request->user()->id);
        }

        $areas = $query->ordered()->get(['id', 'name', 'color']);

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

        // Filtrar por usuario creador (para admin)
        if ($request->user()->isSuperAdmin()) {
            // Superadmin ve todas las áreas
            $areas = $query->ordered()->get();
        } else {
            // Admin normal solo ve sus áreas
            $areas = $query->byCreator($request->user()->id)->ordered()->get();
        }

        return response()->json([
            'areas' => $areas->map(function ($area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'description' => $area->description,
                    'color' => $area->color,
                    'display_order' => $area->display_order,
                    'is_active' => $area->is_active,
                    'employee_count' => $area->employee_count,
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
            'name' => 'required|string|max:255|unique:areas,name',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['color'] = $validated['color'] ?? '#3B82F6';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $area = Area::create($validated);

        return response()->json([
            'success' => true,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'color' => $area->color,
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
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
        }]);

        return response()->json([
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'color' => $area->color,
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
                'employee_count' => $area->employee_count,
                'employees' => $area->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
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
            'name' => 'sometimes|string|max:255|unique:areas,name,'.$area->id,
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $area->update($validated);

        return response()->json([
            'success' => true,
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'description' => $area->description,
                'color' => $area->color,
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
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
     * Obtiene métricas por área.
     * GET /api/areas/metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $areas = Area::withCount('users')->ordered()->get();

        $metrics = $areas->map(function ($area) {
            // Obtener ausencias actuales del mes
            $currentMonth = now()->month;
            $currentYear = now()->year;

            $absencesThisMonth = \App\Models\Absence::whereHas('user', function ($query) use ($area) {
                $query->where('area_id', $area->id);
            })
                ->whereMonth('start', $currentMonth)
                ->whereYear('start', $currentYear)
                ->where('status', 'approved')
                ->count();

            return [
                'id' => $area->id,
                'name' => $area->name,
                'color' => $area->color,
                'total_employees' => $area->employee_count,
                'absences_this_month' => $absencesThisMonth,
                'availability_percentage' => $area->employee_count > 0
                    ? round((($area->employee_count - $absencesThisMonth) / $area->employee_count) * 100, 1)
                    : 100,
            ];
        });

        return response()->json([
            'metrics' => $metrics,
            'summary' => [
                'total_areas' => $areas->count(),
                'total_employees' => $areas->sum('employee_count'),
            ],
        ]);
    }
}
