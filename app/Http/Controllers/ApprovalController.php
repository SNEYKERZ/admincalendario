<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Services\AbsenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected AbsenceService $absenceService
    ) {}

    public function pending(Request $request): JsonResponse
    {
        $chains = AbsenceApprovalChain::where('assigned_to', auth()->id())
            ->where('status', 'pendiente')
            ->with(['absence', 'absence.user', 'absence.type'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($chains);
    }

    public function approve(AbsenceApprovalChain $chain, Request $request): JsonResponse
    {
        $this->authorize('approve', $chain);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->absenceService->approve(
            $chain->absence,
            auth()->user()
        );

        return response()->json([
            'message' => 'Ausencia aprobada',
            'absence' => $updated,
        ]);
    }

    public function reject(AbsenceApprovalChain $chain, Request $request): JsonResponse
    {
        $this->authorize('reject', $chain);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $updated = $this->absenceService->reject(
            $chain->absence,
            auth()->user(),
            $data['reason']
        );

        return response()->json([
            'message' => 'Ausencia rechazada',
            'absence' => $updated,
        ]);
    }

    public function history(Absence $absence): JsonResponse
    {
        $this->authorize('view', $absence);

        $audits = $absence->audits()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($audits);
    }
}
