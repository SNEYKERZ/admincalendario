<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de policies
     */
    protected $policies = [
        \App\Models\Absence::class => \App\Policies\AbsencePolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\VacationYear::class => \App\Policies\VacationYearPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}