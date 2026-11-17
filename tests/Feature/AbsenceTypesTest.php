<?php

use App\Enums\UserRole;
use App\Models\AbsenceType;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Módulo "Tipos de ausencia": cuentas sin tenant, tipos globales del sistema
 * y aislamiento entre empresas.
 */

function absenceTypesTenant(string $name, bool $main = false): Tenant
{
    return Tenant::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'is_active' => true, 'is_main' => $main]);
}

function absenceTypesAdmin(?Tenant $tenant): User
{
    return User::factory()->create([
        'tenant_id' => $tenant?->id,
        'role' => UserRole::ADMIN->value,
        'is_active' => true,
    ]);
}

beforeEach(function () {
    $this->main = absenceTypesTenant('Principal', main: true);
    $this->other = absenceTypesTenant('Otra empresa');

    $this->global = AbsenceType::withoutGlobalScopes()->create(['tenant_id' => null, 'name' => 'Vacaciones globales']);
    $this->own = AbsenceType::withoutGlobalScopes()->create(['tenant_id' => $this->main->id, 'name' => 'Permiso propio']);
    $this->foreign = AbsenceType::withoutGlobalScopes()->create(['tenant_id' => $this->other->id, 'name' => 'Ajeno']);
});

test('admin sin tenant (cuenta legada) abre el módulo y ve los tipos propios y globales', function () {
    $legacy = absenceTypesAdmin(null);

    $this->actingAs($legacy)->get('/admin/absence-types')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AbsenceTypes/Index')
            ->has('types', 1)
            ->where('types.0.name', 'Permiso propio')
            ->has('globalTypes', 1)
            ->where('globalTypes.0.name', 'Vacaciones globales'));
});

test('crear un tipo con una cuenta sin tenant lo guarda en el tenant efectivo, no como global', function () {
    $legacy = absenceTypesAdmin(null);

    $this->actingAs($legacy)->post('/admin/absence-types', ['name' => 'Nuevo tipo'])->assertRedirect();

    expect(AbsenceType::withoutGlobalScopes()->where('name', 'Nuevo tipo')->value('tenant_id'))
        ->toBe($this->main->id);
});

test('un admin no puede modificar ni borrar tipos globales ni de otra empresa', function () {
    $admin = absenceTypesAdmin($this->main);

    $this->actingAs($admin)->put("/admin/absence-types/{$this->global->id}", ['name' => 'Hackeado'])->assertNotFound();
    $this->actingAs($admin)->delete("/admin/absence-types/{$this->global->id}")->assertNotFound();
    $this->actingAs($admin)->put("/admin/absence-types/{$this->foreign->id}", ['name' => 'Hackeado'])->assertNotFound();

    expect(AbsenceType::withoutGlobalScopes()->find($this->global->id)->name)->toBe('Vacaciones globales');
    expect(AbsenceType::withoutGlobalScopes()->find($this->foreign->id)->name)->toBe('Ajeno');

    $this->actingAs($admin)->put("/admin/absence-types/{$this->own->id}", ['name' => 'Permiso editado'])->assertRedirect();
    expect($this->own->fresh()->name)->toBe('Permiso editado');
});

test('el selector de tipos usa los globales cuando la empresa no tiene propios', function () {
    $admin = absenceTypesAdmin($this->other);
    AbsenceType::withoutGlobalScopes()->where('tenant_id', $this->other->id)->delete();

    $this->actingAs($admin)->getJson('/absence-types')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Vacaciones globales');
});

test('el aviso de duplicados sólo aparece si el global sigue en uso por ausencias de la empresa', function () {
    $admin = absenceTypesAdmin($this->main);
    AbsenceType::withoutGlobalScopes()->create(['tenant_id' => $this->main->id, 'name' => 'Vacaciones globales']);

    $this->actingAs($admin)->get('/admin/absence-types')
        ->assertInertia(fn (Assert $page) => $page->has('duplicates', 0));

    \App\Models\Absence::factory()->create([
        'tenant_id' => $this->main->id,
        'user_id' => $admin->id,
        'absence_type_id' => $this->global->id,
    ]);

    $this->actingAs($admin)->get('/admin/absence-types')
        ->assertInertia(fn (Assert $page) => $page
            ->has('duplicates', 1)
            ->where('duplicates.0.name', 'Vacaciones globales')
            ->where('duplicates.0.absences_count', 1));
});
