<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'color' => '#3498db',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Día libre',
                'deducts_vacation' => true,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'color' => '#2ecc71',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Permiso',
                'deducts_vacation' => false,
                'requires_approval' => true,
                'counts_as_hours' => true,
                'color' => '#f39c12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cumpleaños',
                'deducts_vacation' => false,
                'requires_approval' => false,
                'counts_as_hours' => false,
                'color' => '#9b59b6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
