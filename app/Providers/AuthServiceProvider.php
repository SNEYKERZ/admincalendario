<?php

namespace App\Providers;

use App\Auth\TenantAgnosticUserProvider;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
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
        \App\Models\AbsenceApprovalChain::class => \App\Policies\AbsenceApprovalChainPolicy::class,
        \App\Models\EmployeeRequest::class => \App\Policies\EmployeeRequestPolicy::class,
        \App\Models\OvertimeHours::class => \App\Policies\OvertimeHoursPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Carga del usuario autenticado sin TenantScope (ver TenantAgnosticUserProvider).
        Auth::provider('tenant-agnostic-eloquent', function ($app, array $config) {
            return new TenantAgnosticUserProvider($app['hash'], $config['model']);
        });

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
