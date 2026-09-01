<?php

namespace App\Policies;

use App\Models\EmployeeRequest;
use App\Models\User;

class EmployeeRequestPolicy
{
    public function view(User $user, EmployeeRequest $request): bool
    {
        // El empleado que la hizo puede verla
        if ($user->id === $request->user_id) {
            return true;
        }

        // Admin del mismo tenant puede verla
        if ($user->isAdmin() && $user->tenant_id === $request->tenant_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, EmployeeRequest $request): bool
    {
        // Solo el empleado que la hizo puede eliminarla, y solo si está pendiente
        return $user->id === $request->user_id && $request->isPending();
    }
}
