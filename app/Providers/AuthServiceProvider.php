<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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

        // Gate for superadmin access
        Gate::define('superadmin', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Gate for admin access (includes superadmin)
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });
    }
}
