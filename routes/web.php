<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HrDocumentController;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemManagementController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\VacationYearController;
use App\Managers\TenantManager;
use App\Models\AbsenceType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

require __DIR__.'/settings.php';
/*
Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');
*/
/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

// API Pública para el landing page y verificación de licencias
Route::prefix('api/public')->group(function () {
    // Planes de suscripción (para el landing)
    Route::get('/plans', [PublicApiController::class, 'getPlans'])->name('public.plans');

    // Verificación de licencias (para que Ausentra consulte)
    Route::get('/verify-license/{token}', [PublicApiController::class, 'verifyLicense'])->name('public.verify-license');
    Route::get('/license/{token}', [PublicApiController::class, 'getLicenseStatus'])->name('public.license-status');

    // Checkout y payment
    Route::post('/create-checkout-session', [PublicApiController::class, 'createCheckoutSession'])->name('public.checkout');
    Route::get('/payment-callback', [PublicApiController::class, 'paymentCallback'])->name('public.payment-callback');

    // Estadísticas para superadmin (solo con autenticación)
    Route::get('/admin/licenses/stats', [PublicApiController::class, 'getLicenseStats'])
        ->middleware('auth')
        ->name('public.licenses.stats');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/', function () {
        return Auth::check() ? redirect('/dashboard') : redirect('/login');
    });

    // Current user info (for Vue apps)
    Route::get('/me', function () {
        $user = Auth::user();
        $role = $user->role instanceof \App\Enums\UserRole
            ? $user->role->value
            : $user->role;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'is_admin' => in_array($role, ['admin', 'superadmin'], true),
        ]);
    })->name('me');

    // Dashboard API
    Route::get('/dashboard/data', [DashboardController::class, 'index'])->name('dashboard.data');
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('/comunidad', 'Comunidad')->name('comunidad');
    Route::get('/comunidad/data', [CommunityController::class, 'index'])->name('comunidad.data');

    // Calendario
    Route::inertia('/calendario', 'Calendar')->name('calendario');

    Route::inertia('/gestion-usuarios', 'GestionUsuarios')->name('gestion-usuarios');
    Route::get('/gestion-usuarios/data', [VacationController::class, 'index'])->name('gestion-usuarios.data');
    Route::post('/gestion-usuarios/{user}/adjust', [VacationController::class, 'adjust'])->name('gestion-usuarios.adjust');

    // Reportes
    Route::inertia('/reportes', 'Reportes')->name('reportes');
    Route::inertia('/documentos', 'Documents')
        ->middleware('can:admin')
        ->name('documents');

    // Áreas organizacionales
    Route::inertia('/areas', 'Areas')->name('areas');
    Route::get('/areas-list', [AreaController::class, 'list'])->name('areas.list');
    Route::get('/api/areas', [AreaController::class, 'index'])->name('areas.index');
    Route::post('/api/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::get('/api/areas/{area}', [AreaController::class, 'show'])->name('areas.show');
    Route::put('/api/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::delete('/api/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
    Route::get('/api/areas/metrics', [AreaController::class, 'metrics'])->name('areas.metrics');

    // Settings (handled by inertia inside auth group)
    Route::inertia('/legal/terms', 'LegalTerms')->name('legal.terms');
    Route::inertia('/legal/privacy', 'LegalPrivacy')->name('legal.privacy');

    // Users Admin CRUD
    Route::middleware('can:admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    Route::get('/users-list', function () {
        $query = User::query()->withCount([
            'absences as absences_any_status_count' => function ($absenceQuery) {
                $absenceQuery->withoutTenant();
            },
        ]);

        if (request()->boolean('with_absences')) {
            $query->having('absences_any_status_count', '>', 0);
        }

        return $query->with(['vacationYears' => function ($q) {
            $q->where('expires_at', '>=', now());
        }])->orderBy('name')->get()->map(function ($user) {

            $available = $user->vacationYears->sum(function ($year) {
                return $year->allocated_days - $year->used_days;
            });

            return [
                'id' => $user->id,
                'name' => $user->name,
                'identification' => $user->identification,
                'email' => $user->email,
                'photo' => $user->photo_url,
                'available_days' => $available,
            ];
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Absences
    |--------------------------------------------------------------------------
    */
    Route::prefix('absences')->group(function () {
        Route::get('/', [AbsenceController::class, 'index'])->name('absences.index');
        Route::post('/', [AbsenceController::class, 'store'])->name('absences.store');
        Route::get('/{absence}', [AbsenceController::class, 'show'])->name('absences.show');
        Route::put('/{absence}', [AbsenceController::class, 'update'])->name('absences.update');
        Route::post('/{absence}/approve', [AbsenceController::class, 'approve'])->name('absences.approve');
        Route::post('/{absence}/reject', [AbsenceController::class, 'reject'])->name('absences.reject');
        Route::post('/{absence}/pending', [AbsenceController::class, 'pending'])->name('absences.pending');
        Route::delete('/{absence}', [AbsenceController::class, 'destroy'])->name('absences.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Holidays / Feriados
    |--------------------------------------------------------------------------
    */
    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::get('/holidays/countries', [HolidayController::class, 'countries'])->name('holidays.countries');

    /*
    |--------------------------------------------------------------------------
    | Absence Types (para Vue)
    |--------------------------------------------------------------------------
    */
    Route::get('/absence-types', function () {
        $tenantId = app(TenantManager::class)->getTenantId();

        return AbsenceType::withoutGlobalScopes()
            ->select('id', 'name', 'counts_as_hours', 'deducts_vacation', 'default_include_saturday', 'default_include_sunday', 'default_include_holidays')
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->where(function ($innerQuery) use ($tenantId) {
                    $innerQuery
                        ->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            }, function ($query) {
                $query->whereNull('tenant_id');
            })
            ->orderBy('id')
            ->get();
    })->name('absence-types.index');

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        // usuarios
        Route::apiResource('users', UserController::class);

        // vacaciones
        Route::prefix('vacation-years')->group(function () {
            Route::get('/', [VacationYearController::class, 'index'])->name('vacation-years.index');
            Route::post('/', [VacationYearController::class, 'store'])->name('vacation-years.store');
        });

        // roles (solo superadmin)
        Route::prefix('roles')->group(function () {
            Route::get('/', 'App\Http\Controllers\RoleController@index')->name('roles.index');
            Route::get('/{role}', 'App\Http\Controllers\RoleController@show')->name('roles.show');
            Route::post('/', 'App\Http\Controllers\RoleController@store')->name('roles.store');
            Route::put('/{role}', 'App\Http\Controllers\RoleController@update')->name('roles.update');
            Route::delete('/{role}', 'App\Http\Controllers\RoleController@destroy')->name('roles.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::prefix('documents')
        ->middleware('can:admin')
        ->group(function () {
            Route::get('/', [HrDocumentController::class, 'index'])->name('documents.index');
            Route::post('/', [HrDocumentController::class, 'store'])->name('documents.store');
            Route::post('/{document}', [HrDocumentController::class, 'update'])->name('documents.update');
            Route::delete('/{document}', [HrDocumentController::class, 'destroy'])->name('documents.destroy');
            Route::get('/{document}/audits', [HrDocumentController::class, 'audits'])->name('documents.audits');
            Route::get('/{document}/download', [HrDocumentController::class, 'download'])->name('documents.download');
        });

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::inertia('/settings/company', 'SettingsCompany')->name('settings.company');
    Route::get('/settings/company/data', [SettingsController::class, 'index'])->name('settings.company.data');
    Route::put('/settings/company', [SettingsController::class, 'update'])->name('settings.company.update');

    /*
    |--------------------------------------------------------------------------
    | System Management (SuperAdmin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'can:superadmin'])->group(function () {
        Route::inertia('/gestion-sistema', 'SystemManagement')->name('system-management');
        Route::get('/gestion-sistema/api/data', [SystemManagementController::class, 'getData'])->name('system-management.data');
        Route::put('/gestion-sistema/settings', [SystemManagementController::class, 'updateSettings'])->name('system-management.settings');
        Route::post('/gestion-sistema/plans', [SystemManagementController::class, 'storePlan'])->name('system-management.plans.store');
        Route::put('/gestion-sistema/plans/{plan}', [SystemManagementController::class, 'updatePlan'])->name('system-management.plans.update');
        Route::delete('/gestion-sistema/plans/{plan}', [SystemManagementController::class, 'destroyPlan'])->name('system-management.plans.destroy');
        Route::post('/gestion-sistema/subscription/activate', [SystemManagementController::class, 'activateSubscription'])->name('system-management.subscription.activate');
        Route::post('/gestion-sistema/subscription/deactivate', [SystemManagementController::class, 'deactivateSubscription'])->name('system-management.subscription.deactivate');
        Route::post('/gestion-sistema/recalculate-prices', [SystemManagementController::class, 'recalculatePrices'])->name('system-management.recalculate-prices');

        // Announcements
        Route::get('/gestion-sistema/api/announcements', [SystemManagementController::class, 'getAnnouncements'])->name('system-management.announcements.index');
        Route::post('/gestion-sistema/announcements', [SystemManagementController::class, 'storeAnnouncement'])->name('system-management.announcements.store');
        Route::put('/gestion-sistema/announcements/{announcement}', [SystemManagementController::class, 'updateAnnouncement'])->name('system-management.announcements.update');
        Route::delete('/gestion-sistema/announcements/{announcement}', [SystemManagementController::class, 'destroyAnnouncement'])->name('system-management.announcements.destroy');

        // License Tokens API
        Route::get('/gestion-sistema/api/licenses', [PublicApiController::class, 'listLicenses'])->name('system-management.licenses.index');
        Route::post('/gestion-sistema/api/licenses', [PublicApiController::class, 'createLicense'])->name('system-management.licenses.store');
        Route::put('/gestion-sistema/api/licenses/{id}', [PublicApiController::class, 'updateLicense'])->name('system-management.licenses.update');
        Route::delete('/gestion-sistema/api/licenses/{id}', [PublicApiController::class, 'deleteLicense'])->name('system-management.licenses.destroy');
        Route::post('/gestion-sistema/api/licenses/{id}/renew', [PublicApiController::class, 'renewLicense'])->name('system-management.licenses.renew');
        Route::post('/gestion-sistema/api/licenses/{id}/toggle', [PublicApiController::class, 'toggleLicense'])->name('system-management.licenses.toggle');
    });
});
