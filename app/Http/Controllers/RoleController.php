<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Listar todos los roles (para dropdowns y gestión)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::query();

        // Filtrar por estado
        if ($request->has('active') && $request->active !== 'all') {
            $query->where('is_active', $request->active === 'active');
        }

        // Incluir conteo de usuarios
        $roles = $query->ordered()->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'color' => $role->color,
                'is_system' => $role->is_system,
                'is_active' => $role->is_active,
                'display_order' => $role->display_order,
                'user_count' => $role->users()->count(),
                'created_at' => $role->created_at,
            ];
        });

        return response()->json($roles);
    }

    /**
     * Ver un rol específico
     */
    public function show(Role $role): JsonResponse
    {
        $role->loadCount('users');

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'description' => $role->description,
            'color' => $role->color,
            'is_system' => $role->is_system,
            'is_active' => $role->is_active,
            'display_order' => $role->display_order,
            'user_count' => $role->users_count,
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
        ]);
    }

    /**
     * Crear un nuevo rol (solo superadmin)
     */
    public function store(Request $request): JsonResponse
    {
        // Solo superadmin puede crear roles
        $user = $request->user();
        if (! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para crear roles',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|regex:/^[a-z0-9_]+$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $data['is_system'] = false; // Roles creados por usuario no son del sistema
        $data['color'] = $data['color'] ?? '#6B7280';
        $data['is_active'] = $data['is_active'] ?? true;
        $data['display_order'] = $data['display_order'] ?? 0;

        $role = Role::create($data);

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'color' => $role->color,
                'is_system' => $role->is_system,
                'is_active' => $role->is_active,
                'display_order' => $role->display_order,
            ],
            'message' => 'Rol creado exitosamente',
        ], 201);
    }

    /**
     * Actualizar un rol (solo superadmin)
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        // Solo superadmin puede actualizar roles
        $user = $request->user();
        if (! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para modificar roles',
            ], 403);
        }

        // No permitir modificar roles del sistema
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes modificar roles del sistema',
            ], 422);
        }

        $data = $request->validate([
            'display_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $role->update($data);

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'color' => $role->color,
                'is_system' => $role->is_system,
                'is_active' => $role->is_active,
                'display_order' => $role->display_order,
            ],
            'message' => 'Rol actualizado exitosamente',
        ]);
    }

    /**
     * Eliminar un rol (solo superadmin)
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        // Solo superadmin puede eliminar roles
        $user = $request->user();
        if (! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar roles',
            ], 403);
        }

        // No permitir eliminar roles del sistema
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar roles del sistema',
            ], 422);
        }

        // Verificar si hay usuarios con este rol
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar el rol porque hay usuarios asociados',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado exitosamente',
        ]);
    }
}
