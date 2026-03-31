<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case COLLABORATOR = 'colaborador';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::COLLABORATOR => 'Colaborador',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
