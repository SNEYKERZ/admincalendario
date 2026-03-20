<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\VacationYear;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserVacationSeeder extends Seeder
{
    public function run(): void
    {
        $year = now()->year;

        /*
        |----------------------------------------------------------
        | ADMIN 
        |----------------------------------------------------------
        */
        $admin = User::where('email', 'admin@admin.com')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'birth_date' => '1990-01-01',
                'hire_date' => now(),
            ]);
        }

        /*
        |----------------------------------------------------------
        | USUARIO COLABORADOR
        |----------------------------------------------------------
        */
        $user = User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Usuario Prueba',
                'password' => Hash::make('123456'),
                'role' => 'colaborador', // 👈 IMPORTANTE
                'birth_date' => '1995-05-10',
                'hire_date' => now(),
            ]
        );

        /*
        |----------------------------------------------------------
        | VACACIONES (15 días)
        |----------------------------------------------------------
        */
        foreach ([$admin, $user] as $u) {

            VacationYear::updateOrCreate(
                [
                    'user_id' => $u->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => 15,
                    'used_days' => 0,
                    'expires_at' => Carbon::create($year, 12, 31),
                ]
            );
        }
    }
}