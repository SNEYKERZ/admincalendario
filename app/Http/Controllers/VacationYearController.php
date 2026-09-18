<?php

namespace App\Http\Controllers;

use App\Models\VacationYear;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VacationYearController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', VacationYear::class);

        return response()->json(
            VacationYear::where('user_id', $request->user_id)->get()
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
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2000|max:2100',
            'allocated_days' => 'required|integer|min:0|max:60',
        ]);

        $vacation = VacationYear::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'year' => $data['year'],
            ],
            [
                'tenant_id' => $request->user()->tenant_id,
                'allocated_days' => $data['allocated_days'],
                'expires_at' => Carbon::create($data['year'], 12, 31),
            ]
        );

        return response()->json($vacation, 201);
    }
}
