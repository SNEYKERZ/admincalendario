<?php

namespace App\Services\Holidays;

use App\Contracts\HolidayProvider;
use Carbon\CarbonImmutable;

class ColombiaHolidayProvider implements HolidayProvider
{
    public function forYear(int $year): array
    {
        $easter = CarbonImmutable::create($year, 3, 21)->addDays(easter_days($year));

        $holidays = collect([
            ['date' => CarbonImmutable::create($year, 1, 1), 'name' => 'Año Nuevo'],
            ['date' => $this->moveToNextMonday(CarbonImmutable::create($year, 1, 6)), 'name' => 'Día de los Reyes'],
            ['date' => $this->moveToNextMonday(CarbonImmutable::create($year, 3, 19)), 'name' => 'San José'],
            ['date' => $easter->subDays(3), 'name' => 'Jueves Santo'],
            ['date' => $easter->subDays(2), 'name' => 'Viernes Santo'],
            ['date' => CarbonImmutable::create($year, 5, 1), 'name' => 'Día del Trabajo'],
            ['date' => $this->moveToNextMonday($easter->addDays(39)), 'name' => 'Ascensión'],
            ['date' => $this->moveToNextMonday($easter->addDays(60)), 'name' => 'Corpus Christi'],
            ['date' => $this->moveToNextMonday($easter->addDays(68)), 'name' => 'Sagrado Corazón'],
            ['date' => CarbonImmutable::create($year, 7, 20), 'name' => 'Día de la Independencia'],
            ['date' => CarbonImmutable::create($year, 8, 7), 'name' => 'Batalla de Boyacá'],
            ['date' => $this->moveToNextMonday(CarbonImmutable::create($year, 8, 15)), 'name' => 'Asunción'],
            ['date' => $this->moveToNextMonday(CarbonImmutable::create($year, 10, 12)), 'name' => 'Día de la Raza'],
            ['date' => $this->moveToNextMonday(CarbonImmutable::create($year, 11, 1)), 'name' => 'Todos los Santos'],
            ['date' => $this->moveToNextMonday(CarbonImmutable::create($year, 11, 11)), 'name' => 'Independencia de Cartagena'],
            ['date' => CarbonImmutable::create($year, 12, 8), 'name' => 'Inmaculada Concepción'],
            ['date' => CarbonImmutable::create($year, 12, 25), 'name' => 'Navidad'],
        ]);

        return $holidays
            ->mapWithKeys(fn ($item) => [$item['date']->toDateString() => $item['name']])
            ->toArray();
    }

    public static function availableCountries(): array
    {
        return [
            'CO' => 'Colombia',
        ];
    }

    private function moveToNextMonday(CarbonImmutable $date): CarbonImmutable
    {
        return $date->dayOfWeek === CarbonImmutable::MONDAY
            ? $date
            : $date->next(CarbonImmutable::MONDAY);
    }
}
