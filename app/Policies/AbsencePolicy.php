<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Absence;

class AbsencePolicy
{
    /*
    |--------------------------------------------------------------------------
    | VER LISTADO
    |--------------------------------------------------------------------------
    */
    public function viewAny(User $user): bool
    {
        // todos pueden ver (el controller ya filtra)
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | VER DETALLE
    |--------------------------------------------------------------------------
    */
    public function view(User $user, Absence $absence): bool
    {
        return $user->isAdmin() || $user->id === $absence->user_id;
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */
    public function create(User $user): bool
    {
        // todos pueden crear solicitudes
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | APROBAR
    |--------------------------------------------------------------------------
    */
    public function approve(User $user, Absence $absence): bool
    {
        // solo admin
        if (!$user->isAdmin()) {
            return false;
        }

        // no permitir aprobar si ya está aprobado o rechazado
        return $absence->status === 'pendiente';
    }

    /*
    |--------------------------------------------------------------------------
    | RECHAZAR
    |--------------------------------------------------------------------------
    */
    public function reject(User $user, Absence $absence): bool
    {
        // solo admin
        if (!$user->isAdmin()) {
            return false;
        }

        // solo se puede rechazar si está pendiente
        return $absence->status === 'pendiente';
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */
    public function delete(User $user, Absence $absence): bool
    {
        // admin puede todo
        if ($user->isAdmin()) {
            return true;
        }

        // usuario solo puede eliminar si:
        // - es suyo
        // - está pendiente (no aprobado)
        return $user->id === $absence->user_id
            && $absence->status === 'pendiente';
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR (BONUS - recomendado)
    |--------------------------------------------------------------------------
    */
    public function update(User $user, Absence $absence): bool
    {
        // admin puede editar todo
        if ($user->isAdmin()) {
            return true;
        }

        // usuario solo puede editar si está pendiente
        return $user->id === $absence->user_id
            && $absence->status === 'pendiente';
    }
}