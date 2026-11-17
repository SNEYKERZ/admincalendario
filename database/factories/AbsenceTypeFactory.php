<?php

namespace Database\Factories;

use App\Models\AbsenceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbsenceType>
 */
class AbsenceTypeFactory extends Factory
{
    protected $model = AbsenceType::class;

    public function definition(): array
    {
        return [
            'name' => 'Permiso '.fake()->unique()->word(),
            'deducts_vacation' => false,
            'requires_approval' => true,
            'counts_as_hours' => false,
            'default_include_saturday' => false,
            'default_include_sunday' => false,
            'default_include_holidays' => false,
            'color' => '#10B981',
            'max_days_limit' => null,
        ];
    }

    public function vacation(): static
    {
        return $this->state(fn () => [
            'name' => 'Vacaciones '.fake()->unique()->word(),
            'deducts_vacation' => true,
        ]);
    }
}
