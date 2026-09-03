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
        $query = OvertimeHours::with(['user', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        return response()->json($query->orderByDesc('date')->get());
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

        $overtime = $this->overtimeService->create($data);

        return response()->json($overtime->load(['user', 'approver']), 201);
    }

    public function show(OvertimeHours $overtimeHours): JsonResponse
    {
        return response()->json($overtimeHours->load(['user', 'approver']));
    }

    public function approve(OvertimeHours $overtimeHours): JsonResponse
    {
        return response()->json(
            $this->overtimeService->approve($overtimeHours, auth()->user())
        );
    }

    public function reject(OvertimeHours $overtimeHours, Request $request): JsonResponse
    {
        $reason = $request->input('reason', '');

        return response()->json(
            $this->overtimeService->reject($overtimeHours, auth()->user(), $reason)
        );
    }

    public function destroy(OvertimeHours $overtimeHours): JsonResponse
    {
        if ($overtimeHours->isApproved()) {
            return response()->json([
                'message' => 'No se pueden eliminar horas extra aprobadas',
            ], 403);
        }

        $overtimeHours->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }
}
