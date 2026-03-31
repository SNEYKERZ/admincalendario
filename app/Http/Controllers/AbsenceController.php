<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbsenceRequest;
use App\Models\Absence;
use App\Services\AbsenceService;
use App\Services\VacationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function __construct(
        protected AbsenceService $absenceService,
        protected VacationService $vacationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Absence::class);

        $query = Absence::with(['user', 'type', 'approver']);

        if (! auth()->user()->isAdmin()) {
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

    public function store(StoreAbsenceRequest $request): JsonResponse
    {
        $this->authorize('create', Absence::class);

        $absence = $this->absenceService->create($request->validatedData());

        return response()->json($absence, 201);
    }

    public function show(Absence $absence): JsonResponse
    {
        $this->authorize('view', $absence);

        return response()->json($absence->load(['user', 'type', 'approver']));
    }

    public function approve(Absence $absence): JsonResponse
    {
        $this->authorize('approve', $absence);

        return response()->json(
            $this->absenceService->approve($absence, auth()->user())
        );
    }

    public function reject(Absence $absence): JsonResponse
    {
        $this->authorize('reject', $absence);

        return response()->json(
            $this->absenceService->reject($absence, auth()->user())
        );
    }

    public function pending(Absence $absence): JsonResponse
    {
        $this->authorize('pending', $absence);

        return response()->json(
            $this->absenceService->pending($absence, auth()->user())
        );
    }

    public function destroy(Absence $absence): JsonResponse
    {
        $this->authorize('delete', $absence);

        // Restaurar días de vacaciones ANTES de eliminar si estaba aprobado
        if ($absence->status === 'aprobado' && $absence->type->deducts_vacation) {
            $this->vacationService->restoreDays($absence->user, $absence->total_days);
        }

        $absence->delete();

        return response()->json([
            'message' => 'Registro eliminado',
        ]);
    }

    public function update(Request $request, Absence $absence): JsonResponse
    {
        $this->authorize('update', $absence);

        $data = $request->validate([
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'include_saturday' => ['sometimes', 'boolean'],
            'include_sunday' => ['sometimes', 'boolean'],
            'include_holidays' => ['sometimes', 'boolean'],
            'holiday_country' => ['sometimes', 'string', 'size:2'],
            'reason' => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->absenceService->update($absence, $data)
        );
    }
}
