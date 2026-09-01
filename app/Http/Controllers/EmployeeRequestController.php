<?php

namespace App\Http\Controllers;

use App\Models\EmployeeRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeRequestController extends Controller
{
    public function index()
    {
        $requests = auth()->user()
            ->employeeRequests()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('EmployeeRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function create()
    {
        return Inertia::render('EmployeeRequests/Create', [
            'requestTypes' => [
                'certificado' => 'Certificado Laboral',
                'permiso_especial' => 'Permiso Especial',
                'documento' => 'Documento',
                'cambio_datos' => 'Cambio de Datos',
                'otro' => 'Otra Solicitud',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:certificado,permiso_especial,documento,cambio_datos,otro',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $employeeRequest = auth()->user()->employeeRequests()->create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return redirect()->route('solicitudes.show', $employeeRequest)
            ->with('success', 'Solicitud creada correctamente');
    }

    public function show(EmployeeRequest $employeeRequest)
    {
        $this->authorize('view', $employeeRequest);

        $employeeRequest->load('user', 'assignedTo', 'approvals.approver');
        $employeeRequest->approvals->each(function ($approval) {
            $approval->approver_name = $approval->approver->name;
        });

        return Inertia::render('EmployeeRequests/Show', [
            'request' => $employeeRequest,
        ]);
    }

    public function destroy(EmployeeRequest $employeeRequest)
    {
        $this->authorize('delete', $employeeRequest);

        if (!$employeeRequest->isPending()) {
            return back()->with('error', 'Solo se pueden eliminar solicitudes pendientes');
        }

        $employeeRequest->delete();

        return redirect()->route('solicitudes.index')
            ->with('success', 'Solicitud eliminada correctamente');
    }
}
