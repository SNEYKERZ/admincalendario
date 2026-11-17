<?php

use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\Tenant;
use App\Models\User;

/*
 * Migración 2026_11_17_000006: unificación de tipos globales en tipos de empresa.
 */

function unifyMigration(): object
{
    return require database_path('migrations/2026_11_17_000006_unify_global_absence_types_into_tenants.php');
}

function unifyType(?int $tenantId, string $name, array $attrs = []): AbsenceType
{
    return AbsenceType::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => $tenantId,
        'name' => $name,
        'deducts_vacation' => false,
        'counts_as_hours' => false,
    ], $attrs));
}

function unifyAbsence(User $user, AbsenceType $type, string $status = 'aprobado'): Absence
{
    return Absence::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'absence_type_id' => $type->id,
        'status' => $status,
    ]);
}

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Empresa', 'slug' => 'empresa', 'is_active' => true, 'is_main' => true]);
    $this->other = Tenant::create(['name' => 'Otra', 'slug' => 'otra', 'is_active' => true, 'is_main' => false]);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->otherUser = User::factory()->create(['tenant_id' => $this->other->id]);
});

test('mueve ausencias al equivalente de la empresa cuando la regla de saldo coincide', function () {
    $global = unifyType(null, 'Vacaciones', ['deducts_vacation' => true]);
    $local = unifyType($this->tenant->id, 'vacaciones ', ['deducts_vacation' => true, 'counts_as_hours' => true]);
    $absence = unifyAbsence($this->user, $global);

    unifyMigration()->up();

    expect($absence->fresh()->absence_type_id)->toBe($local->id);
    expect(AbsenceType::withoutGlobalScopes()->find($global->id))->not->toBeNull();
});

test('no mueve nada si la regla de descuento de vacaciones difiere', function () {
    $global = unifyType(null, 'Día libre', ['deducts_vacation' => false]);
    unifyType($this->tenant->id, 'Dia libre', ['deducts_vacation' => true]);
    $absence = unifyAbsence($this->user, $global);

    unifyMigration()->up();

    expect($absence->fresh()->absence_type_id)->toBe($global->id);
});

test('si la empresa no tiene el equivalente crea una copia con las mismas reglas', function () {
    $global = unifyType(null, 'Cumpleaños', ['deducts_vacation' => false, 'color' => '#9b59b6']);
    $absence = unifyAbsence($this->otherUser, $global);

    unifyMigration()->up();

    $copy = AbsenceType::withoutGlobalScopes()->where('tenant_id', $this->other->id)->where('name', 'Cumpleaños')->first();

    expect($copy)->not->toBeNull()
        ->and($copy->color)->toBe('#9b59b6')
        ->and($absence->fresh()->absence_type_id)->toBe($copy->id);
});

test('es idempotente', function () {
    $global = unifyType(null, 'Permiso');
    unifyType($this->tenant->id, 'Permiso');
    unifyAbsence($this->user, $global);
    unifyAbsence($this->otherUser, $global);

    unifyMigration()->up();
    $typesAfterFirstRun = AbsenceType::withoutGlobalScopes()->count();
    unifyMigration()->up();

    expect(AbsenceType::withoutGlobalScopes()->count())->toBe($typesAfterFirstRun);
    expect(Absence::withoutGlobalScopes()->where('absence_type_id', $global->id)->count())->toBe(0);
});
