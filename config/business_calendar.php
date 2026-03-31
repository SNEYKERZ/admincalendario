<?php

return [
    'default_country' => env('BUSINESS_CALENDAR_COUNTRY', 'CO'),

    /*
    |--------------------------------------------------------------------------
    | Proveedores de feriados por país
    |--------------------------------------------------------------------------
    | Configura aquí los proveedores de feriados por país.
    | La clase debe implementar \App\Contracts\HolidayProvider
    */
    'providers' => [
        'CO' => \App\Services\Holidays\ColombiaHolidayProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Horas laborales por día
    |--------------------------------------------------------------------------
    */
    'hours_per_day' => 8,
];
