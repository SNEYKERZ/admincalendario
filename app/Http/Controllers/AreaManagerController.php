<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaManagerController extends Controller
{
    public function setManager(Area $area, Request $request): JsonResponse
    {
        $this->authorize('update', $area);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $area->update([
            'area_manager_id' => $data['user_id'],
        ]);

        return response()->json($area->load('manager'));
    }

    public function removeManager(Area $area): JsonResponse
    {
        $this->authorize('update', $area);

        $area->update(['area_manager_id' => null]);

        return response()->json([
            'message' => 'Jefe de área removido',
        ]);
    }
}
