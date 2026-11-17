<?php

namespace App\Policies;

use App\Managers\TenantManager;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $this->manages($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $this->manages($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        // Nadie puede eliminarse a sí mismo desde la gestión de usuarios.
        if ($user->id === $model->id) {
            return false;
        }

        return $this->manages($user, $model);
    }

    /**
     * Un admin gestiona usuarios de su propio tenant, excepto superadmins.
     * El superadmin gestiona a cualquiera.
     */
    protected function manages(User $user, User $model): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($model->isSuperAdmin()) {
            return false;
        }

        $tenants = app(TenantManager::class);

        return $tenants->tenantIdForUser($user) === $tenants->tenantIdForUser($model);
    }
}
