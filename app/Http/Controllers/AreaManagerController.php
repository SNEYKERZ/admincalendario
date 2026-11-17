<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaManagerController extends Controller
{
    // Autorización: la ruta exige can:admin y el binding de {area} ya está
    // acotado al tenant. Antes llamaba a authorize('update', $area) sin que
    // existiera AreaPolicy, por lo que siempre respondía 403.
    public function setManager(Area $area, Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', TenantRule::exists('users')],
        ]);

        $area->update([
            'area_manager_id' => $data['user_id'],
        ]);

        return response()->json($area->load('manager'));
    }

    public function removeManager(Area $area): JsonResponse
    {
        $area->update(['area_manager_id' => null]);

        return response()->json([
            'message' => 'Jefe de área removido',
        ]);
    }
}
