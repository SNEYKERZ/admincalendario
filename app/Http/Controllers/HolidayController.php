<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;

class HolidayController extends Controller
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    /**
     * Get holidays for a specific year and country
     * GET /holidays?year=2026&country=CO
     */
    public function index(): JsonResponse
    {
        $year = request()->get('year', now()->year);
        $country = strtoupper(request()->get('country', config('business_calendar.default_country', 'CO')));

        $holidays = $this->holidayService->getHolidaysForYear((int) $year, $country);

        // Format for calendar: { date: '2026-04-02', title: 'Jueves Santo' }
        $events = collect($holidays)->map(fn ($name, $date) => [
            'date' => $date,
            'title' => $name,
        ])->values();

        return response()->json($events);
    }

    /**
     * Get available countries
     * GET /holidays/countries
     */
    public function countries(): JsonResponse
    {
        $countries = $this->holidayService->getAvailableCountries();

        return response()->json($countries);
    }
}
