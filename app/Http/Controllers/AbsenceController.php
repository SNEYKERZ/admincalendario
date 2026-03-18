<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use Illuminate\Http\Request;
use App\Services\AbsenceService;
use App\Http\Requests\StoreAbsenceRequest;

class AbsenceController extends Controller
{
    public function __construct(
        protected AbsenceService $absenceService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $this->authorize('viewAny', Absence::class);

        $query = Absence::with(['user', 'type']);

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start') && $request->filled('end')) {

            $start = $request->start;
            $end = $request->end;

            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_datetime', [$start, $end])
                  ->orWhereBetween('end_datetime', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_datetime', '<=', $start)
                         ->where('end_datetime', '>=', $end);
                  });
            });
        }

        return response()->json($query->get());
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function store(StoreAbsenceRequest $request)
    {
        $this->authorize('create', Absence::class);

        $absence = $this->absenceService->create(
            $request->validatedData()
        );

        return response()->json($absence, 201);
    }

    /*
    |--------------------------------------------------------------------------
    | VER DETALLE
    |--------------------------------------------------------------------------
    */

    public function show(Absence $absence)
    {
        $this->authorize('view', $absence);

        return response()->json(
            $absence->load(['user', 'type', 'approver'])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APROBAR
    |--------------------------------------------------------------------------
    */

    public function approve(Absence $absence)
    {
        $this->authorize('approve', $absence);

        $this->absenceService->approve($absence, auth()->user());

        return response()->json([
            'message' => 'Solicitud aprobada correctamente'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RECHAZAR
    |--------------------------------------------------------------------------
    */

    public function reject(Absence $absence)
    {
        $this->authorize('reject', $absence);

        $absence->update([
            'status' => 'rechazado'
        ]);

        return response()->json([
            'message' => 'Solicitud rechazada'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    public function destroy(Absence $absence)
    {
        $this->authorize('delete', $absence);

        $absence->delete();

        return response()->json([
            'message' => 'Registro eliminado'
        ]);
    }

    public function update(StoreAbsenceRequest $request, Absence $absence)
    {
        $this->authorize('update', $absence);

        $absence = $this->absenceService->update(
            $absence,
            $request->validatedData()
        );

        return response()->json($absence);
    }
}