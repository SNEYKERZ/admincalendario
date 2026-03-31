<?php

namespace App\Services;

use App\Contracts\HolidayProvider;
use App\Services\Holidays\ColombiaHolidayProvider;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class HolidayService
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $cache = [];

    public function __construct(
        protected Container $container
    ) {}

    /**
     * Check if a specific date is a holiday
     */
    public function isHoliday(CarbonInterface $date, ?string $country = null): bool
    {
        $country = strtoupper($country ?: config('business_calendar.default_country', 'CO'));
        $cacheKey = $country.':'.$date->year;

        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->providerFor($country)->forYear($date->year);
        }

        return isset($this->cache[$cacheKey][$date->toDateString()]);
    }

    /**
     * Get the name of a holiday for a specific date
     */
    public function getHolidayName(CarbonInterface $date, ?string $country = null): ?string
    {
        $country = strtoupper($country ?: config('business_calendar.default_country', 'CO'));
        $cacheKey = $country.':'.$date->year;

        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->providerFor($country)->forYear($date->year);
        }

        return $this->cache[$cacheKey][$date->toDateString()] ?? null;
    }

    /**
     * Get all holidays for a year and country
     *
     * @return array<string, string> ['2026-04-02' => 'Jueves Santo', ...]
     */
    public function getHolidaysForYear(int $year, ?string $country = null): array
    {
        $country = strtoupper($country ?: config('business_calendar.default_country', 'CO'));
        $cacheKey = $country.':'.$year;

        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->providerFor($country)->forYear($year);
        }

        return $this->cache[$cacheKey];
    }

    /**
     * Get all available countries with their display names
     *
     * @return array<string, string>
     */
    public function getAvailableCountries(): array
    {
        $countries = [];

        // Get from config providers
        $providers = config('business_calendar.providers', []);

        foreach ($providers as $countryCode => $providerClass) {
            if ($this->container->bound($providerClass)) {
                $provider = $this->container->make($providerClass);
                $countries[$countryCode] = $provider::availableCountries()[$countryCode] ?? $countryCode;
            }
        }

        // Add defaults if not in config
        $defaults = [
            'CO' => 'Colombia',
        ];

        foreach ($defaults as $code => $name) {
            if (! isset($countries[$code])) {
                $countries[$code] = $name;
            }
        }

        return $countries;
    }

    /**
     * Obtener proveedor de feriados para un país
     * Usa el container para permitir extensión vía ServiceProviders
     */
    private function providerFor(string $country): HolidayProvider
    {
        // Intentar resolver del container primero (permite extensión)
        $providerClass = config("business_calendar.providers.{$country}");

        if ($providerClass && $this->container->bound($providerClass)) {
            return $this->container->make($providerClass);
        }

        // Proveedores por defecto hardcodeados (fallback)
        $defaults = [
            'CO' => ColombiaHolidayProvider::class,
        ];

        if (! isset($defaults[$country])) {
            throw new InvalidArgumentException("No hay proveedor de festivos para {$country}");
        }

        return $this->container->make($defaults[$country]);
    }

    /**
     * Registrar un proveedor de feriados (útil para testing o extensión)
     */
    public function registerProvider(string $country, string $providerClass): void
    {
        config(["business_calendar.providers.{$country}" => $providerClass]);
    }
}
