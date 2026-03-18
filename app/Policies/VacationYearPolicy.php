<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VacationYear;

class VacationYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, VacationYear $model): bool
    {
        return $user->isAdmin() || $user->id === $model->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VacationYear $model): bool
    {
        return $user->isAdmin();
    }
}