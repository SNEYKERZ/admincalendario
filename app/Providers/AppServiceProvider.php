<?php

namespace App\Providers;

use App\Services\Holidays\ColombiaHolidayProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Absence::class => \App\Policies\AbsencePolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\VacationYear::class => \App\Policies\VacationYearPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el TenantManager como singleton
        $this->app->singleton(TenantManager::class);

        // Registrar proveedores de feriados por defecto
        $this->app->singleton(ColombiaHolidayProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
