<?php

namespace App\Policies;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\User;

class AbsencePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Absence $absence): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $absenceOwner = $absence->relationLoaded('user')
            ? $absence->user
            : $absence->user()->first();

        if ($absenceOwner && $absenceOwner->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function approve(User $user, Absence $absence): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, Absence $absence): bool
    {
        return $user->isAdmin();
    }

    public function pending(User $user, Absence $absence): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Absence $absence): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $absence->user_id
            && $absence->status === AbsenceStatus::PENDING;
    }

    public function update(User $user, Absence $absence): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $absence->user_id
            && $absence->status === AbsenceStatus::PENDING;
    }
}

