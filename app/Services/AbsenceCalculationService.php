<?php

namespace App\Services;

use App\Models\AbsenceType;
use Carbon\Carbon;

class AbsenceCalculationService
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    public function resolveOptions(AbsenceType $type, array $data): array
    {
        return [
            'include_saturday' => array_key_exists('include_saturday', $data)
                ? filter_var($data['include_saturday'], FILTER_VALIDATE_BOOL)
                : $type->default_include_saturday,
            'include_sunday' => array_key_exists('include_sunday', $data)
                ? filter_var($data['include_sunday'], FILTER_VALIDATE_BOOL)
                : $type->default_include_sunday,
            'include_holidays' => array_key_exists('include_holidays', $data)
                ? filter_var($data['include_holidays'], FILTER_VALIDATE_BOOL)
                : $type->default_include_holidays,
            'holiday_country' => strtoupper($data['holiday_country'] ?? config('business_calendar.default_country', 'CO')),
        ];
    }

    public function calculate(AbsenceType $type, Carbon $start, Carbon $end, array $options): array
    {
        if ($type->counts_as_hours) {
            $hours = round($start->diffInMinutes($end) / 60, 2);

            return [
                ...$options,
                'total_hours' => $hours,
                'total_days' => round($hours / 8, 2),
            ];
        }

        $current = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();
        $days = 0;

        while ($current->lte($lastDay)) {
            if ($this->shouldCountDate($current, $options)) {
                $days++;
            }

            $current->addDay();
        }

        return [
            ...$options,
            'total_days' => (float) $days,
            'total_hours' => (float) ($days * config('business_calendar.hours_per_day', 8)),
        ];
    }

    public function shouldCountDate(Carbon $date, array $options): bool
    {
        if (! $options['include_saturday'] && $date->isSaturday()) {
            return false;
        }

        if (! $options['include_sunday'] && $date->isSunday()) {
            return false;
        }

        if (
            ! $options['include_holidays']
            && $this->holidayService->isHoliday($date, $options['holiday_country'])
        ) {
            return false;
        }

        return true;
    }
}
