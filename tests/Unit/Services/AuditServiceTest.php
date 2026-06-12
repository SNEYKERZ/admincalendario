<?php

use App\Models\Absence;
use App\Models\AbsenceAudit;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AuditService::class);
    $this->user = User::factory()->create();
    $this->absence = Absence::factory()->create();
});

test('log action creates audit record', function () {
    $this->actingAs($this->user);

    $audit = $this->service->logAction(
        $this->absence,
        'created',
        ['total_days' => 5],
        null
    );

    expect($audit)->toBeInstanceOf(AbsenceAudit::class);
    expect($audit->absence_id)->toBe($this->absence->id);
    expect($audit->user_id)->toBe($this->user->id);
    expect($audit->action)->toBe('created');
    expect($audit->changes)->toEqual(['total_days' => 5]);
    expect($audit->ip_address)->not()->toBeNull();
});

test('log action with reason', function () {
    $this->actingAs($this->user);

    $audit = $this->service->logAction(
        $this->absence,
        'rejected',
        ['status' => 'rechazado'],
        'Insuficiente saldo'
    );

    expect($audit->reason)->toBe('Insuficiente saldo');
});

test('audit records are queryable', function () {
    $this->actingAs($this->user);

    $this->service->logAction($this->absence, 'created', [], null);
    $this->service->logAction($this->absence, 'approved', [], null);

    $audits = $this->absence->audits;
    expect($audits->count())->toBe(2);
    expect($audits->first()->action)->toBe('created');
    expect($audits->last()->action)->toBe('approved');
});
