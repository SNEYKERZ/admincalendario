<?php

namespace App\Contracts;

interface HolidayProvider
{
    /**
     * @return array<string>
     */
    public function forYear(int $year): array;
}
