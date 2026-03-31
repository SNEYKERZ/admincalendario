<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ColaboradorSeeder extends Seeder
{
    public function run(): void
    {
        $colaboradores = [
            [
                'name' => 'Juan Pérez',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@empresa.com',
                'identification' => '1234567890',
                'phone' => '3001234567',
                'birth_date' => '1990-05-15',
                'hire_date' => now()->subMonths(6),
            ],
            [
                'name' => 'María González',
                'first_name' => 'María',
                'last_name' => 'González',
                'email' => 'maria.gonzalez@empresa.com',
                'identification' => '1234567891',
                'phone' => '3001234568',
                'birth_date' => '1992-08-22',
                'hire_date' => now()->subMonths(4),
            ],
            [
                'name' => 'Carlos López',
                'first_name' => 'Carlos',
                'last_name' => 'López',
                'email' => 'carlos.lopez@empresa.com',
                'identification' => '1234567892',
                'phone' => '3001234569',
                'birth_date' => '1988-03-10',
                'hire_date' => now()->subMonths(8),
            ],
            [
                'name' => 'Ana Martínez',
                'first_name' => 'Ana',
                'last_name' => 'Martínez',
                'email' => 'ana.martinez@empresa.com',
                'identification' => '1234567893',
                'phone' => '3001234570',
                'birth_date' => '1995-11-28',
                'hire_date' => now()->subMonths(2),
            ],
            [
                'name' => 'Pedro Rodríguez',
                'first_name' => 'Pedro',
                'last_name' => 'Rodríguez',
                'email' => 'pedro.rodriguez@empresa.com',
                'identification' => '1234567894',
                'phone' => '3001234571',
                'birth_date' => '1993-07-03',
                'hire_date' => now()->subMonths(10),
            ],
        ];

        foreach ($colaboradores as $colaborador) {
            // Only create if doesn't exist
            if (! User::where('email', $colaborador['email'])->exists()) {
                User::create([
                    'name' => $colaborador['name'],
                    'first_name' => $colaborador['first_name'],
                    'last_name' => $colaborador['last_name'],
                    'email' => $colaborador['email'],
                    'password' => Hash::make('password123'),
                    'identification' => $colaborador['identification'],
                    'phone' => $colaborador['phone'],
                    'role' => UserRole::COLLABORATOR->value,
                    'birth_date' => $colaborador['birth_date'],
                    'hire_date' => $colaborador['hire_date'],
                ]);
            }
        }
    }
}
