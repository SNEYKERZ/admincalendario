<?php

namespace App\Policies;

use App\Models\OvertimeHours;
use App\Models\User;

class OvertimeHoursPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isAdmin()) {
            return $user->id === $overtime->user_id && $user->tenant_id === $overtime->tenant_id;
        }

        return $user->tenant_id === $overtime->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function store(User $user, ?User $targetUser = null): bool
    {
        // SuperAdmin can create for anyone in any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Non-admin can only create for themselves
        if (!$user->isAdmin()) {
            return !$targetUser || $user->id === $targetUser->id;
        }

        // Admin can create for anyone in their tenant
        if ($targetUser) {
            return $user->tenant_id === $targetUser->tenant_id;
        }

        return true;
    }

    public function update(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isAdmin()) {
            return $user->id === $overtime->user_id && $overtime->status === 'pending';
        }

        return $user->tenant_id === $overtime->tenant_id;
    }

    public function delete(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isAdmin()) {
            return $user->id === $overtime->user_id && $overtime->status === 'pending';
        }

        return $user->tenant_id === $overtime->tenant_id;
    }

    public function approve(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $user->tenant_id === $overtime->tenant_id;
    }

    public function reject(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $user->tenant_id === $overtime->tenant_id;
    }
}
