<?php

use App\Enums\AbsenceStatus;
use App\Enums\UserRole;
use App\Managers\TenantManager;
use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\AbsenceType;
use App\Models\Area;
use App\Models\OvertimeHours;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VacationYear;
use App\Services\Holidays\ColombiaHolidayProvider;
use App\Services\OvertimeHoursService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Empresa', 'slug' => 'empresa', 'is_active' => true, 'is_main' => true]);
    $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::ADMIN->value, 'is_active' => true]);
    $this->employee = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => UserRole::COLLABORATOR->value, 'is_active' => true]);

    VacationYear::create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->employee->id,
        'year' => now()->year,
        'allocated_days' => 15,
        'used_days' => 0,
        'expires_at' => now()->addYear(),
    ]);

    $this->vacationType = AbsenceType::factory()->vacation()->create(['tenant_id' => $this->tenant->id]);
});

afterEach(function () {
    app(TenantManager::class)->reset();
});

function vacationAbsence(object $test, array $attrs = []): Absence
{
    return Absence::factory()->create(array_merge([
        'tenant_id' => $test->tenant->id,
        'user_id' => $test->employee->id,
        'absence_type_id' => $test->vacationType->id,
        'total_days' => 3,
    ], $attrs));
}

function usedDays(User $user): float
{
    return (float) VacationYear::withoutGlobalScopes()->where('user_id', $user->id)->sum('used_days');
}

test('la aprobación final descuenta los días de vacaciones', function () {
    $absence = vacationAbsence($this);

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();

    expect(Absence::withoutGlobalScopes()->find($absence->id)->status)->toBe(AbsenceStatus::APPROVED);
    expect(usedDays($this->employee))->toBe(3.0);
});

test('no se puede aprobar dos veces ni descontar doble', function () {
    $absence = vacationAbsence($this);

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();
    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertStatus(422);

    expect(usedDays($this->employee))->toBe(3.0);
});

test('no se aprueba si el saldo no alcanza', function () {
    $absence = vacationAbsence($this, ['total_days' => 20]);

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertStatus(422);

    expect(Absence::withoutGlobalScopes()->find($absence->id)->status)->toBe(AbsenceStatus::PENDING);
    expect(usedDays($this->employee))->toBe(0.0);
});

test('rechazar una ausencia aprobada la marca rechazada y restaura el saldo', function () {
    $absence = vacationAbsence($this);
    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/reject", ['reason' => 'Error'])->assertOk();

    $fresh = Absence::withoutGlobalScopes()->find($absence->id);
    expect($fresh->status)->toBe(AbsenceStatus::REJECTED);
    expect($fresh->rejected_at)->not->toBeNull();
    expect(usedDays($this->employee))->toBe(0.0);
});

test('eliminar una ausencia aprobada restaura el saldo', function () {
    $absence = vacationAbsence($this);
    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();

    $this->actingAs($this->admin)->deleteJson("/absences/{$absence->id}")->assertOk();

    expect(usedDays($this->employee))->toBe(0.0);
});

test('volver a pendiente restaura saldo y rehace la cadena de aprobación', function () {
    $absence = vacationAbsence($this);
    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/pending")->assertOk();

    expect(usedDays($this->employee))->toBe(0.0);
    expect(AbsenceApprovalChain::where('absence_id', $absence->id)->where('status', 'pendiente')->count())->toBeGreaterThan(0);

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();
    expect(usedDays($this->employee))->toBe(3.0);
});

test('la edición valida saldo y solo aplica a ausencias pendientes', function () {
    $absence = vacationAbsence($this);
    $start = now()->addMonths(2)->startOfWeek();

    // 30 días hábiles > 15 de saldo.
    $this->actingAs($this->employee)->putJson("/absences/{$absence->id}", [
        'start_datetime' => $start->toDateTimeString(),
        'end_datetime' => $start->copy()->addWeeks(6)->toDateTimeString(),
    ])->assertStatus(422);

    $this->actingAs($this->admin)->postJson("/absences/{$absence->id}/approve")->assertOk();

    $this->actingAs($this->employee)->putJson("/absences/{$absence->id}", [
        'start_datetime' => $start->toDateTimeString(),
        'end_datetime' => $start->copy()->addDay()->toDateTimeString(),
    ])->assertForbidden();
});

test('no se permiten solapamientos al crear', function () {
    $start = now()->addMonth()->startOfWeek()->setTime(0, 0);
    vacationAbsence($this, ['start_datetime' => $start, 'end_datetime' => $start->copy()->addDays(2)->setTime(23, 59)]);

    $this->actingAs($this->employee)->postJson('/absences', [
        'absence_type_id' => $this->vacationType->id,
        'start_datetime' => $start->copy()->addDay()->setTime(8, 0)->toDateTimeString(),
        'end_datetime' => $start->copy()->addDays(4)->setTime(17, 0)->toDateTimeString(),
    ])->assertStatus(422);
});

test('el jefe de área no aprueba su propia ausencia en la cadena', function () {
    $area = Area::factory()->create(['tenant_id' => $this->tenant->id, 'area_manager_id' => $this->employee->id]);
    $this->employee->update(['area_id' => $area->id]);
    $type = AbsenceType::factory()->create(['tenant_id' => $this->tenant->id]);
    $start = now()->addMonth()->startOfWeek()->setTime(8, 0);

    $id = $this->actingAs($this->employee)->postJson('/absences', [
        'absence_type_id' => $type->id,
        'start_datetime' => $start->toDateTimeString(),
        'end_datetime' => $start->copy()->setTime(17, 0)->toDateTimeString(),
    ])->assertCreated()->json('id');

    $chains = AbsenceApprovalChain::where('absence_id', $id)->get();
    expect($chains->pluck('assigned_to'))->not->toContain($this->employee->id);
    expect($chains->pluck('assigned_to'))->toContain($this->admin->id);
});

test('festivos de Colombia incluyen San Pedro y San Pablo y Semana Santa correcta', function () {
    $holidays = (new ColombiaHolidayProvider)->forYear(2026);

    expect($holidays)->toHaveKey('2026-06-29');
    expect($holidays['2026-06-29'])->toBe('San Pedro y San Pablo');
    expect($holidays)->toHaveKey('2026-04-03'); // Viernes Santo
    expect($holidays)->toHaveKey('2026-01-12'); // Reyes trasladado a lunes
    expect($holidays)->toHaveCount(18);
});

test('horas extra: el tope semanal cuenta registros pendientes', function () {
    $this->actingAs($this->employee);
    $service = app(OvertimeHoursService::class);
    $monday = now()->addWeek()->startOfWeek();

    for ($i = 0; $i < 6; $i++) {
        $service->create([
            'date' => $monday->copy()->addDays($i)->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'Cierre de mes contable',
        ]);
    }

    expect(fn () => $service->create([
        'date' => $monday->copy()->addDays(6)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '19:00',
        'reason' => 'Cierre de mes contable',
    ]))->toThrow(ValidationException::class);

    expect(OvertimeHours::count())->toBe(6);
});

test('horas extra: no se permiten dos registros el mismo día', function () {
    $this->actingAs($this->employee);
    $service = app(OvertimeHoursService::class);
    $date = now()->addWeek()->startOfWeek()->toDateString();

    $service->create(['date' => $date, 'start_time' => '18:00', 'end_time' => '19:00', 'reason' => 'Soporte producción']);

    expect(fn () => $service->create(['date' => $date, 'start_time' => '19:00', 'end_time' => '20:00', 'reason' => 'Soporte producción']))
        ->toThrow(ValidationException::class);
});
