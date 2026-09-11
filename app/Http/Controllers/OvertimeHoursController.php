<?php

namespace App\Http\Controllers;

use App\Models\OvertimeHours;
use App\Services\OvertimeHoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeHoursController extends Controller
{
    public function __construct(
        protected OvertimeHoursService $overtimeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OvertimeHours::class);

        $query = OvertimeHours::with(['user', 'approver']);

        // Non-admin users can only see their own records
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        return response()->json($query->orderByDesc('date')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'client' => 'sometimes|string|max:255',
            'project' => 'sometimes|string|max:255',
            'reason' => 'required|string|min:10',
        ]);

        $targetUser = $data['user_id'] ? \App\Models\User::find($data['user_id']) : auth()->user();
        $this->authorize('store', $targetUser);

        $overtime = $this->overtimeService->create($data);

        return response()->json($overtime->load(['user', 'approver']), 201);
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $records = $request->validate([
            'records' => 'required|array|min:1',
            'records.*.user_id' => 'sometimes|exists:users,id',
            'records.*.date' => 'required|date',
            'records.*.start_time' => 'required|date_format:H:i',
            'records.*.end_time' => 'required|date_format:H:i',
            'records.*.client' => 'sometimes|string|max:255',
            'records.*.project' => 'sometimes|string|max:255',
            'records.*.reason' => 'required|string|min:10',
        ]);

        // Check authorization for each batch record's target user
        foreach ($records['records'] as $record) {
            $targetUser = $record['user_id'] ? \App\Models\User::find($record['user_id']) : auth()->user();
            $this->authorize('store', $targetUser);
        }

        $overtimes = $this->overtimeService->createBatch($records['records']);

        return response()->json(
            array_map(fn ($o) => $o->load(['user', 'approver']), $overtimes),
            201
        );
    }

    public function update(OvertimeHours $overtimeHours, Request $request): JsonResponse
    {
        $this->authorize('update', $overtimeHours);

        $data = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'client' => 'sometimes|string|max:255',
            'project' => 'sometimes|string|max:255',
            'reason' => 'required|string|min:10',
        ]);

        $overtime = $this->overtimeService->update($overtimeHours, $data);

        return response()->json($overtime->load(['user', 'approver']));
    }

    public function destroy(OvertimeHours $overtimeHours): JsonResponse
    {
        $this->authorize('delete', $overtimeHours);

        if ($overtimeHours->isApproved()) {
            return response()->json([
                'message' => 'No se pueden eliminar horas extra aprobadas',
            ], 403);
        }

        $overtimeHours->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }
}
