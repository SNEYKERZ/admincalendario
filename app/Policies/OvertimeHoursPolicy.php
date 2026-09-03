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
            return $user->id === $overtime->user_id;
        }

        return $user->tenant_id === $overtime->record()->first()?->user?->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function store(User $user, ?User $targetUser = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isAdmin()) {
            return true;
        }

        if ($targetUser) {
            return $user->area_id === $targetUser->area_id;
        }

        return true;
    }

    public function update(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->id === $overtime->user_id;
    }

    public function delete(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $user->area_id === $overtime->user->area_id;
    }

    public function approve(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $user->area_id === $overtime->user->area_id;
    }

    public function reject(User $user, OvertimeHours $overtime): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $user->area_id === $overtime->user->area_id;
    }
}
