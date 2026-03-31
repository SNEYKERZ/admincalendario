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
     * @var array<string, array<string>>
     */
    private array $cache = [];

    public function __construct(
        protected Container $container
    ) {}

    public function isHoliday(CarbonInterface $date, ?string $country = null): bool
    {
        $country = strtoupper($country ?: config('business_calendar.default_country', 'CO'));
        $cacheKey = $country.':'.$date->year;

        if (! isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->providerFor($country)->forYear($date->year);
        }

        return in_array($date->toDateString(), $this->cache[$cacheKey], true);
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
