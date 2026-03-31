<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case COLLABORATOR = 'colaborador';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadministrador',
            self::ADMIN => 'Administrador',
            self::COLLABORATOR => 'Colaborador',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN || $this === self::SUPERADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SUPERADMIN;
    }
}
