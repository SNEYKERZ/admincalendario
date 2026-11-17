<?php

use App\Enums\UserRole;
use App\Managers\TenantManager;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
 * Pruebas de regresión de la auditoría de seguridad (aislamiento multi-tenant,
 * autorización y autenticación).
 */

function makeTenant(string $name, bool $main = false): Tenant
{
    return Tenant::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'is_active' => true, 'is_main' => $main]);
}

function makeUser(Tenant $tenant, UserRole $role = UserRole::COLLABORATOR, array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'role' => $role->value,
        'is_active' => true,
    ], $attrs));
}

function absenceFor(User $user, array $attrs = []): Absence
{
    return Absence::factory()->create(array_merge([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'absence_type_id' => AbsenceType::factory()->create(['tenant_id' => $user->tenant_id])->id,
    ], $attrs));
}

beforeEach(function () {
    $this->tenantA = makeTenant('Empresa A', main: true);
    $this->tenantB = makeTenant('Empresa B');
});

afterEach(function () {
    app(TenantManager::class)->reset();
});

test('TenantManager se registra como singleton', function () {
    expect(app(TenantManager::class))->toBe(app(TenantManager::class));
});

test('un usuario no ve ausencias de otro tenant ni puede elegir tenant con X-Tenant-ID', function () {
    $userA = makeUser($this->tenantA);
    $userB = makeUser($this->tenantB);
    $absenceA = absenceFor($userA);
    $absenceB = absenceFor($userB);

    $ids = collect(
        $this->actingAs($userB)
            ->withHeader('X-Tenant-ID', (string) $this->tenantA->id)
            ->getJson('/absences')
            ->assertOk()
            ->json()
    )->pluck('id');

    expect($ids)->toContain($absenceB->id)->not->toContain($absenceA->id);

    $this->actingAs($userB)->getJson("/absences/{$absenceA->id}")->assertNotFound();
    $this->actingAs($userB)->deleteJson("/absences/{$absenceA->id}")->assertNotFound();
});

test('las ausencias nuevas guardan el tenant del usuario', function () {
    $userA = makeUser($this->tenantA);
    $type = AbsenceType::factory()->create(['tenant_id' => $this->tenantA->id]);
    $start = now()->addMonth()->startOfWeek()->setTime(8, 0);

    $id = $this->actingAs($userA)->postJson('/absences', [
        'absence_type_id' => $type->id,
        'start_datetime' => $start->toDateTimeString(),
        'end_datetime' => $start->copy()->addDay()->setTime(17, 0)->toDateTimeString(),
    ])->assertCreated()->json('id');

    expect(Absence::withoutGlobalScopes()->find($id)->tenant_id)->toBe($this->tenantA->id);
});

test('un colaborador no puede usar el CRUD de usuarios ni auto-promoverse', function () {
    $collab = makeUser($this->tenantA);

    $this->actingAs($collab)->getJson('/admin/users')->assertForbidden();
    $this->actingAs($collab)->postJson('/admin/users', [
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@y.com',
        'password' => 'secret123', 'role' => 'admin',
    ])->assertForbidden();
    $this->actingAs($collab)->putJson("/admin/users/{$collab->id}", ['role' => 'admin'])->assertForbidden();

    expect($collab->fresh()->isAdmin())->toBeFalse();
});

test('un admin no puede ver, editar ni borrar a un superadmin de su tenant', function () {
    $admin = makeUser($this->tenantA, UserRole::ADMIN);
    $super = makeUser($this->tenantA, UserRole::SUPERADMIN);

    $list = collect($this->actingAs($admin)->getJson('/admin/users')->assertOk()->json())->pluck('id');
    expect($list)->not->toContain($super->id);

    $this->actingAs($admin)->putJson("/admin/users/{$super->id}", ['email' => 'hijack@evil.com'])->assertForbidden();
    $this->actingAs($admin)->deleteJson("/admin/users/{$super->id}")->assertForbidden();
    expect($super->fresh()->email)->not->toBe('hijack@evil.com');
});

test('un admin no puede editar usuarios de otro tenant', function () {
    $adminA = makeUser($this->tenantA, UserRole::ADMIN);
    $userB = makeUser($this->tenantB);

    $this->actingAs($adminA)->putJson("/admin/users/{$userB->id}", ['first_name' => 'Hack'])->assertNotFound();
});

test('reportes y configuración de empresa requieren rol admin', function () {
    $collab = makeUser($this->tenantA);

    $this->actingAs($collab)->getJson('/reports')->assertForbidden();
    $this->actingAs($collab)->get('/reports/export')->assertForbidden();
    $this->actingAs($collab)->getJson('/reports/filters-data')->assertForbidden();
    $this->actingAs($collab)->putJson('/settings/company', ['company_name' => 'Hack'])->assertForbidden();
});

test('el reporte de ausencias de un admin solo incluye su tenant', function () {
    $adminA = makeUser($this->tenantA, UserRole::ADMIN);
    $userB = makeUser($this->tenantB);
    absenceFor($userB);

    $this->actingAs($adminA)
        ->getJson('/reports?type=absences&start='.now()->toDateString().'&end='.now()->addYear()->toDateString())
        ->assertOk()
        ->assertJsonPath('total', 0);
});

test('las estadísticas de licencias son solo para superadmin', function () {
    $admin = makeUser($this->tenantA, UserRole::ADMIN);

    $this->actingAs($admin)->getJson('/api/public/admin/licenses/stats')->assertForbidden();
});

test('el checkout simulado está deshabilitado en producción', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['services.checkout.mock_enabled' => false]);

    $this->getJson('/api/public/payment-callback')->assertStatus(503);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->postJson('/api/public/create-checkout-session', [])
        ->assertStatus(503);
});

test('el cambio de contraseña se guarda hasheado', function () {
    $user = makeUser($this->tenantA);

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    $stored = $user->fresh()->password;
    expect($stored)->not->toBe('new-password');
    expect(Hash::check('new-password', $stored))->toBeTrue();
});

test('un usuario desactivado no puede iniciar sesión', function () {
    $user = makeUser($this->tenantA, attrs: ['is_active' => false]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un usuario de un tenant inactivo pierde la sesión', function () {
    $user = makeUser($this->tenantB);
    $this->tenantB->update(['is_active' => false]);

    $this->actingAs($user)->getJson('/dashboard/data')->assertForbidden();
    $this->assertGuest();
});

test('un usuario con 2FA es enviado al desafío de dos factores', function () {
    $user = makeUser($this->tenantA, attrs: [
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('documentos RRHH rechazan extensiones peligrosas y firmas que no son imagen', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $collab = makeUser($this->tenantA);

    $this->actingAs($collab)->postJson('/documents', [
        'title' => 'Malicioso',
        'document_type' => 'otro',
        'document_mode' => 'user',
        'file' => \Illuminate\Http\UploadedFile::fake()->create('xss.html', 1, 'text/html'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    $this->actingAs($collab)->postJson('/documents', [
        'title' => 'Contrato',
        'document_type' => 'otro',
        'document_mode' => 'user',
        'file' => \Illuminate\Http\UploadedFile::fake()->create('contrato.pdf', 10, 'application/pdf'),
    ])->assertCreated();
});

test('páginas de reportes y gestión de usuarios sólo para admins; superadmin global va a su panel', function () {
    $collab = makeUser($this->tenantA);
    $admin = makeUser($this->tenantA, UserRole::ADMIN);
    $super = makeUser($this->tenantA, UserRole::SUPERADMIN);

    // Plan con todos los módulos: así el 403 del colaborador sólo puede venir de can:admin.
    $this->mock(\App\Services\ModuleAccessService::class, function ($mock) {
        $mock->shouldReceive('userCanAccessModule')->andReturnTrue();
        $mock->shouldReceive('getTenantEnabledModuleSlugs')->andReturn([]);
        $mock->shouldReceive('effectiveTenant')->andReturn($this->tenantA);
    });

    $this->actingAs($collab)->get('/reportes')->assertForbidden();
    $this->actingAs($collab)->get('/gestion-usuarios')->assertForbidden();
    $this->actingAs($admin)->get('/reportes')->assertOk();
    $this->actingAs($admin)->get('/gestion-usuarios')->assertOk();

    $this->actingAs($collab)->get('/dashboard')->assertOk();
    $this->actingAs($super)->get('/dashboard')->assertRedirect('/superadmin/dashboard');
});

test('usuario legado sin tenant mantiene la sesión tras iniciar sesión', function () {
    $legacy = makeUser($this->tenantA, UserRole::ADMIN, ['tenant_id' => null]);

    $this->post(route('login.store'), ['email' => $legacy->email, 'password' => 'password'])
        ->assertRedirect('/dashboard');

    // Como en una petición real: el guard vuelve a cargar al usuario desde la sesión.
    $this->app['auth']->forgetGuards();

    // La siguiente petición debe seguir autenticada (antes rebotaba a /login).
    $this->get('/dashboard')->assertOk();
    $this->assertAuthenticatedAs($legacy);
});

test('con cuentas duplicadas por email el login usa la que tiene tenant', function () {
    $legacy = makeUser($this->tenantA, UserRole::ADMIN, ['tenant_id' => null, 'email' => 'dup@example.test']);
    $current = makeUser($this->tenantA, UserRole::ADMIN, ['email' => 'dup@example.test']);

    expect($legacy->id)->toBeLessThan($current->id);

    $this->post(route('login.store'), ['email' => 'dup@example.test', 'password' => 'password'])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($current);
});

test('usuario legado sin tenant usa los módulos del tenant principal', function () {
    $legacy = makeUser($this->tenantA, UserRole::ADMIN, ['tenant_id' => null]);
    $service = app(\App\Services\ModuleAccessService::class);

    expect($service->effectiveTenant($legacy)?->id)->toBe($this->tenantA->id);

    $super = makeUser($this->tenantA, UserRole::SUPERADMIN, ['tenant_id' => null]);
    expect($service->effectiveTenant($super))->toBeNull();
});
