<?php

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\Area;
use App\Models\User;
use App\Services\ApprovalChainService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ApprovalChainService::class);
});

test('create approval chain with area manager', function () {
    $areaManager = User::factory()->create(['is_area_manager' => true]);
    $area = Area::factory()->create(['area_manager_id' => $areaManager->id]);
    $user = User::factory()->create(['area_id' => $area->id]);
    $absence = Absence::factory()->create(['user_id' => $user->id]);

    $this->service->createApprovalChain($absence);

    $chains = $absence->approvalChains;
    expect($chains->count())->toBeGreaterThan(0);
    expect($chains->first()->assigned_to)->toBe($areaManager->id);
    expect($chains->first()->approval_level)->toBe(AbsenceApprovalChain::LEVEL_AREA_MANAGER);
});

test('create approval chain without area manager', function () {
    $area = Area::factory()->create(['area_manager_id' => null]);
    $user = User::factory()->create(['area_id' => $area->id]);
    $absence = Absence::factory()->create(['user_id' => $user->id]);

    $this->service->createApprovalChain($absence);

    $chains = $absence->approvalChains;
    expect($chains->whereIn('approval_level', [AbsenceApprovalChain::LEVEL_AREA_MANAGER])->count())->toBe(0);
});

test('approve absence chain updates status', function () {
    $approver = User::factory()->create();
    $area = Area::factory()->create(['area_manager_id' => $approver->id]);
    $user = User::factory()->create(['area_id' => $area->id]);
    $absence = Absence::factory()->create(['user_id' => $user->id]);

    $this->service->createApprovalChain($absence);
    $chain = $absence->approvalChains()->first();

    $updated = $this->service->approve($chain, $approver, 'OK');

    expect($updated->status)->toBe('aprobado');
    expect($updated->completed_at)->not()->toBeNull();
    expect($updated->notes)->toBe('OK');
});

test('reject absence chain updates status', function () {
    $rejector = User::factory()->create();
    $area = Area::factory()->create(['area_manager_id' => $rejector->id]);
    $user = User::factory()->create(['area_id' => $area->id]);
    $absence = Absence::factory()->create(['user_id' => $user->id]);

    $this->service->createApprovalChain($absence);
    $chain = $absence->approvalChains()->first();

    $updated = $this->service->reject($chain, $rejector, 'Insuficiente saldo');

    expect($updated->status)->toBe('rechazado');
    expect($updated->notes)->toBe('Insuficiente saldo');
    expect($absence->fresh()->status)->toBe('rechazado');
    expect($absence->fresh()->rejection_reason)->toBe('Insuficiente saldo');
});
