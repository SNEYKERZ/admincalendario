<?php

namespace App\Http\Controllers;

use App\Models\AbsenceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsenceTypeController extends Controller
{
    public function index()
    {
        $this->authorize('admin');

        $tenant = auth()->user()->tenant;

        $types = AbsenceType::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $globalTypes = AbsenceType::whereNull('tenant_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('AbsenceTypes/Index', [
            'types' => $types,
            'globalTypes' => $globalTypes,
            'duplicates' => $this->findDuplicates($types, $globalTypes),
        ]);
    }

    private function findDuplicates($tenantTypes, $globalTypes)
    {
        $duplicates = [];
        $tenantNames = $tenantTypes->pluck('name')->toArray();
        $globalNames = $globalTypes->pluck('name')->toArray();

        foreach ($tenantNames as $name) {
            if (in_array($name, $globalNames)) {
                $duplicates[] = [
                    'name' => $name,
                    'tenant_count' => $tenantTypes->where('name', $name)->count(),
                    'global_count' => $globalTypes->where('name', $name)->count(),
                ];
            }
        }

        return $duplicates;
    }

    public function store(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'deducts_vacation' => 'boolean',
            'requires_approval' => 'boolean',
            'counts_as_hours' => 'boolean',
            'default_include_saturday' => 'boolean',
            'default_include_sunday' => 'boolean',
            'default_include_holidays' => 'boolean',
            'color' => 'nullable|string',
            'max_days_limit' => 'nullable|integer',
        ]);

        AbsenceType::create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return back()->with('success', 'Tipo de ausencia creado');
    }

    public function update(AbsenceType $absenceType, Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'deducts_vacation' => 'boolean',
            'requires_approval' => 'boolean',
            'counts_as_hours' => 'boolean',
            'default_include_saturday' => 'boolean',
            'default_include_sunday' => 'boolean',
            'default_include_holidays' => 'boolean',
            'color' => 'nullable|string',
            'max_days_limit' => 'nullable|integer',
        ]);

        $absenceType->update($validated);

        return back()->with('success', 'Tipo de ausencia actualizado');
    }

    public function destroy(AbsenceType $absenceType)
    {
        $this->authorize('admin');

        if ($absenceType->absences()->exists()) {
            return back()->with('error', 'No se puede eliminar un tipo con ausencias registradas');
        }

        $absenceType->delete();

        return back()->with('success', 'Tipo de ausencia eliminado');
    }
}
