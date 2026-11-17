<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VacationYear;
use App\Support\TenantRule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VacationYearController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', VacationYear::class);

        return response()->json(
            VacationYear::where('user_id', (int) $request->user_id)->get()
        );
    }

    /**
     * Crea o actualiza (upsert) la asignación de días de vacaciones de un
     * usuario para un año. Usado para corregir/aumentar manualmente el saldo,
     * por ejemplo cuando un usuario quedó sin asignación (0 días disponibles).
     */
    public function store(Request $request)
    {
        $this->authorize('create', VacationYear::class);

        $data = $request->validate([
            'user_id' => ['required', TenantRule::exists('users')],
            'year' => 'required|integer|min:2000|max:2100',
            'allocated_days' => 'required|integer|min:0|max:60',
        ]);

        $target = User::query()->findOrFail($data['user_id']);
        $this->authorize('update', $target);

        $existing = VacationYear::query()
            ->where('user_id', $target->id)
            ->where('year', $data['year'])
            ->first();

        if ($existing && $data['allocated_days'] < $existing->used_days) {
            return response()->json([
                'message' => 'No se pueden asignar menos días de los ya disfrutados en ese año.',
                'errors' => ['allocated_days' => ['No se pueden asignar menos días de los ya disfrutados en ese año.']],
            ], 422);
        }

        $vacation = VacationYear::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'year' => $data['year'],
            ],
            [
                'tenant_id' => $target->tenant_id,
                'allocated_days' => $data['allocated_days'],
                'expires_at' => Carbon::create($data['year'], 12, 31),
            ]
        );

        return response()->json($vacation, 201);
    }
}
