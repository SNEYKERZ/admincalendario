<?php

namespace Database\Factories;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\AbsenceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absence>
 */
class AbsenceFactory extends Factory
{
    protected $model = Absence::class;

    public function definition(): array
    {
        // Lunes a miércoles de una semana futura sin festivos relevantes.
        $start = now()->addMonth()->startOfWeek()->setTime(0, 0);
        $end = $start->copy()->addDays(2)->setTime(23, 59);

        return [
            'user_id' => User::factory(),
            'absence_type_id' => AbsenceType::factory(),
            'start_datetime' => $start,
            'end_datetime' => $end,
            'include_saturday' => false,
            'include_sunday' => false,
            'include_holidays' => false,
            'holiday_country' => 'CO',
            'total_days' => 3,
            'total_hours' => 24,
            'status' => AbsenceStatus::PENDING->value,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => AbsenceStatus::APPROVED->value,
            'approved_at' => now(),
        ]);
    }
}
