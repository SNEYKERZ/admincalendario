<?php

use App\Enums\UserRole;
use App\Models\User;

test('authenticated users can visit community page', function () {
    $user = User::factory()->create([
        'role' => UserRole::COLLABORATOR->value,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('comunidad'))
        ->assertOk();
});

test('community data hides superadmins for collaborators and admins', function () {
    $superadmin = User::factory()->create([
        'name' => 'Super Admin',
        'email' => 'super.community@test.com',
        'role' => UserRole::SUPERADMIN->value,
        'is_active' => true,
    ]);

    User::factory()->create([
        'name' => 'Admin Visible',
        'email' => 'admin.community@test.com',
        'role' => UserRole::ADMIN->value,
        'is_active' => true,
    ]);

    User::factory()->create([
        'name' => 'Colaborador Visible',
        'email' => 'colab.community@test.com',
        'role' => UserRole::COLLABORATOR->value,
        'is_active' => true,
    ]);

    $adminViewer = User::factory()->create([
        'role' => UserRole::ADMIN->value,
        'is_active' => true,
    ]);

    $collaboratorViewer = User::factory()->create([
        'role' => UserRole::COLLABORATOR->value,
        'is_active' => true,
    ]);

    $adminResponse = $this->actingAs($adminViewer)
        ->getJson(route('comunidad.data'))
        ->assertOk()
        ->json('users');

    expect(collect($adminResponse)->pluck('id'))->not->toContain($superadmin->id);

    $collaboratorResponse = $this->actingAs($collaboratorViewer)
        ->getJson(route('comunidad.data'))
        ->assertOk()
        ->json('users');

    expect(collect($collaboratorResponse)->pluck('id'))->not->toContain($superadmin->id);
});

test('community data includes superadmins for superadmin viewer', function () {
    $targetSuperadmin = User::factory()->create([
        'name' => 'Super Included',
        'email' => 'super.included@test.com',
        'role' => UserRole::SUPERADMIN->value,
        'is_active' => true,
    ]);

    $viewerSuperadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN->value,
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewerSuperadmin)
        ->getJson(route('comunidad.data'))
        ->assertOk()
        ->json('users');

    expect(collect($response)->pluck('id'))->toContain($targetSuperadmin->id);
});

