<?php

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\Area;
use App\Models\User;
use App\Services\ApprovalChainService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->areaManager = User::factory()->create(['is_area_manager' => true]);
    $this->area = Area::factory()->create(['area_manager_id' => $this->areaManager->id]);
    $this->employee = User::factory()->create(['area_id' => $this->area->id]);

    $this->absence = Absence::factory()->create(['user_id' => $this->employee->id]);

    $approvalService = app(ApprovalChainService::class);
    $approvalService->createApprovalChain($this->absence);
});

test('area manager can view pending approvals', function () {
    $response = $this->actingAs($this->areaManager)
        ->getJson('/approvals/pending');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'absence_id', 'status', 'approval_level']
        ]
    ]);
});

test('non area manager cannot view pending approvals', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($other)
        ->getJson('/approvals/pending');

    $response->assertStatus(200);
    expect($response->json('data'))->toEqual([]);
});

test('area manager can approve absence', function () {
    $chain = $this->absence->approvalChains()->first();

    $response = $this->actingAs($this->areaManager)
        ->postJson("/approvals/{$chain->id}/approve", [
            'notes' => 'Aprobado'
        ]);

    $response->assertStatus(200);
    expect($chain->fresh()->status)->toBe('aprobado');
});

test('area manager can reject absence', function () {
    $chain = $this->absence->approvalChains()->first();

    $response = $this->actingAs($this->areaManager)
        ->postJson("/approvals/{$chain->id}/reject", [
            'reason' => 'Insuficiente saldo'
        ]);

    $response->assertStatus(200);
    expect($chain->fresh()->status)->toBe('rechazado');
    expect($this->absence->fresh()->rejection_reason)->toBe('Insuficiente saldo');
});

test('cannot approve chain if not assigned to user', function () {
    $other = User::factory()->create();
    $chain = $this->absence->approvalChains()->first();

    $response = $this->actingAs($other)
        ->postJson("/approvals/{$chain->id}/approve", [
            'notes' => 'test'
        ]);

    $response->assertStatus(403);
});

test('can view absence history with audits', function () {
    $response = $this->actingAs($this->areaManager)
        ->getJson("/absences/{$this->absence->id}/history");

    $response->assertStatus(200);
    expect($response->json())->toBeArray();
});
