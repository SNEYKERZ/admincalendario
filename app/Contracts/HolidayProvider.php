<?php

namespace App\Contracts;

interface HolidayProvider
{
    /**
     * @return array<string, string> Array of ['date' => 'name'] - date in Y-m-d format
     */
    public function forYear(int $year): array;

    /**
     * Get list of supported countries with their display names
     *
     * @return array<string, string> ['CO' => 'Colombia', 'MX' => 'México', etc.]
     */
    public static function availableCountries(): array;
}
