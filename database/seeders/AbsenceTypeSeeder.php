<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AbsenceType;

class AbsenceTypeSeeder extends Seeder
{
    public function run(): void
    {
        AbsenceType::insert([
            [
                'name' => 'Vacaciones',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => false,
                'default_include_saturday' => false,
                'default_include_sunday' => false,
                'default_include_holidays' => false,
                'color' => '#3498db',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dia libre',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#2ecc71',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Permiso',
                'deducts_vacation' => false,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#f39c12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cumpleaños',
                'deducts_vacation' => false,
                'requires_approval' => false,
                'counts_as_hours' => false,
                'default_include_saturday' => true,
                'default_include_sunday' => true,
                'default_include_holidays' => true,
                'color' => '#9b59b6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

