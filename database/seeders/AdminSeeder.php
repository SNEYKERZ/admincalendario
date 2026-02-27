<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'birth_date' => '1990-01-01',
            'hire_date' => now(),
        ]);
    }
}