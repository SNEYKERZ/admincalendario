<?php

namespace App\Http\Controllers;

use App\Models\EmployeeRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminRequestController extends Controller
{
    public function index()
    {
        $this->authorize('admin');

        $status = request('status', '');
        $type = request('type', '');

        $query = auth()->user()->tenant
            ->employeeRequests()
            ->with(['user', 'assignedTo', 'approvals.approver'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->byStatus($status);
        }

        if ($type) {
            $query->byType($type);
        }

        $requests = $query->paginate(20);

        $requests->getCollection()->transform(function ($request) {
            $request->user_name = $request->user->name;
            $request->user_email = $request->user->email;
            $request->approvals->each(function ($approval) {
                $approval->approver_name = $approval->approver->name;
            });
            return $request;
        });

        return Inertia::render('AdminRequests/Index', [
            'requests' => $requests,
            'status' => $status,
            'type' => $type,
            'statuses' => [
                'pendiente' => 'Pendiente',
                'aprobado' => 'Aprobado',
                'rechazado' => 'Rechazado',
            ],
            'requestTypes' => [
                'certificado' => 'Certificado Laboral',
                'permiso_especial' => 'Permiso Especial',
                'documento' => 'Documento',
                'cambio_datos' => 'Cambio de Datos',
                'otro' => 'Otra Solicitud',
            ],
        ]);
    }

    public function approve(EmployeeRequest $employeeRequest)
    {
        $this->authorize('admin');

        if (!$employeeRequest->isPending()) {
            return back()->with('error', 'Solo se pueden aprobar solicitudes pendientes');
        }

        $comment = request('comment');

        $employeeRequest->approve(auth()->user(), $comment);

        return back()->with('success', 'Solicitud aprobada correctamente');
    }

    public function reject(EmployeeRequest $employeeRequest)
    {
        $this->authorize('admin');

        $validated = request()->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$employeeRequest->isPending()) {
            return back()->with('error', 'Solo se pueden rechazar solicitudes pendientes');
        }

        $employeeRequest->reject(auth()->user(), $validated['reason']);

        return back()->with('success', 'Solicitud rechazada correctamente');
    }
}
