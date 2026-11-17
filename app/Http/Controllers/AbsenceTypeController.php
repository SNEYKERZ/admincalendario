<?php

namespace App\Http\Controllers;

use App\Managers\TenantManager;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsenceTypeController extends Controller
{
    public function index()
    {
        $this->authorize('admin');

        $tenantId = $this->currentTenantId();

        $types = AbsenceType::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Tipos globales del sistema (tenant_id NULL): el TenantScope los oculta.
        $globalTypes = AbsenceType::withoutGlobalScope(TenantScope::class)
            ->whereNull('tenant_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('AbsenceTypes/Index', [
            'types' => $types,
            'globalTypes' => $globalTypes,
            'duplicates' => $this->findDuplicates($types, $globalTypes, $tenantId),
        ]);
    }

    /**
     * Tipos con el mismo nombre en la empresa y en el catálogo global, sólo si
     * el global sigue en uso por ausencias de la empresa (si no, el global es
     * únicamente plantilla del sistema y no genera confusión).
     */
    private function findDuplicates($tenantTypes, $globalTypes, int $tenantId): array
    {
        $duplicates = [];
        $tenantNames = $tenantTypes->pluck('name')->unique()->all();

        foreach ($tenantNames as $name) {
            $globalIds = $globalTypes->where('name', $name)->pluck('id');

            if ($globalIds->isEmpty()) {
                continue;
            }

            $absencesCount = Absence::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('absence_type_id', $globalIds)
                ->count();

            if ($absencesCount === 0) {
                continue;
            }

            $duplicates[] = [
                'name' => $name,
                'tenant_count' => $tenantTypes->where('name', $name)->count(),
                'global_count' => $globalIds->count(),
                'absences_count' => $absencesCount,
            ];
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
            ...$validated,
            // Siempre en el tenant efectivo: con users.tenant_id NULL se creaba un tipo global.
            'tenant_id' => $this->currentTenantId(),
        ]);

        return back()->with('success', 'Tipo de ausencia creado');
    }

    public function update(AbsenceType $absenceType, Request $request)
    {
        $this->authorize('admin');
        $this->ensureEditable($absenceType);

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
        $this->ensureEditable($absenceType);

        if ($absenceType->absences()->exists()) {
            return back()->with('error', 'No se puede eliminar un tipo con ausencias registradas');
        }

        $absenceType->delete();

        return back()->with('success', 'Tipo de ausencia eliminado');
    }

    /**
     * Tenant efectivo (el del usuario o, para cuentas legadas sin tenant, el
     * principal). El superadmin en contexto global no gestiona tipos de empresa.
     */
    private function currentTenantId(): int
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        abort_unless($tenantId, 403, 'Selecciona una empresa para gestionar sus tipos de ausencia.');

        return $tenantId;
    }

    /** Sólo se editan/eliminan tipos propios del tenant, nunca los globales del sistema. */
    private function ensureEditable(AbsenceType $absenceType): void
    {
        abort_unless((int) $absenceType->tenant_id === $this->currentTenantId(), 403, 'Los tipos globales del sistema no se pueden modificar.');
    }
}
