<?php

namespace App\Services\Holidays;

use App\Contracts\HolidayProvider;
use Carbon\CarbonImmutable;

class ColombiaHolidayProvider implements HolidayProvider
{
    public function forYear(int $year): array
    {
        $easter = CarbonImmutable::create($year, 3, 21)->addDays(easter_days($year));

        return collect([
            CarbonImmutable::create($year, 1, 1),
            $this->moveToNextMonday(CarbonImmutable::create($year, 1, 6)),
            $this->moveToNextMonday(CarbonImmutable::create($year, 3, 19)),
            $easter->subDays(3),
            $easter->subDays(2),
            CarbonImmutable::create($year, 5, 1),
            $this->moveToNextMonday($easter->addDays(39)),
            $this->moveToNextMonday($easter->addDays(60)),
            $this->moveToNextMonday($easter->addDays(68)),
            CarbonImmutable::create($year, 7, 20),
            CarbonImmutable::create($year, 8, 7),
            $this->moveToNextMonday(CarbonImmutable::create($year, 8, 15)),
            $this->moveToNextMonday(CarbonImmutable::create($year, 10, 12)),
            $this->moveToNextMonday(CarbonImmutable::create($year, 11, 1)),
            $this->moveToNextMonday(CarbonImmutable::create($year, 11, 11)),
            CarbonImmutable::create($year, 12, 8),
            CarbonImmutable::create($year, 12, 25),
        ])
            ->map(fn (CarbonImmutable $date) => $date->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function moveToNextMonday(CarbonImmutable $date): CarbonImmutable
    {
        return $date->dayOfWeek === CarbonImmutable::MONDAY
            ? $date
            : $date->next(CarbonImmutable::MONDAY);
    }
}
