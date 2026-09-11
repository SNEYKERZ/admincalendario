<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        // Superadmin can update anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular admin can only update users from their own tenant
        return $user->tenant_id === $model->tenant_id;
    }

    public function delete(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        // Superadmin can delete anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular admin can only delete users from their own tenant
        return $user->tenant_id === $model->tenant_id;
    }
    
}