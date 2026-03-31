<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemManagementController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\VacationYearController;
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
/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

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
            'is_admin' => $role === 'admin',
        ]);
    })->name('me');

    // Dashboard API
    Route::get('/dashboard/data', [DashboardController::class, 'index'])->name('dashboard.data');
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    // Calendario
    Route::inertia('/calendario', 'Calendar')->name('calendario');

    Route::inertia('/gestion-usuarios', 'GestionUsuarios')->name('gestion-usuarios');
    Route::get('/gestion-usuarios/data', [VacationController::class, 'index'])->name('gestion-usuarios.data');
    Route::post('/gestion-usuarios/{user}/adjust', [VacationController::class, 'adjust'])->name('gestion-usuarios.adjust');

    // Reportes
    Route::inertia('/reportes', 'Reportes')->name('reportes');

    // Settings (handled by inertia inside auth group)
    Route::inertia('/legal/terms', 'LegalTerms')->name('legal.terms');
    Route::inertia('/legal/privacy', 'LegalPrivacy')->name('legal.privacy');

    // Users Admin CRUD
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/users-list', function () {
        return User::with(['vacationYears' => function ($q) {
            $q->where('expires_at', '>=', now());
        }])->get()->map(function ($user) {

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
        return AbsenceType::select('id', 'name', 'counts_as_hours', 'deducts_vacation', 'default_include_saturday', 'default_include_sunday', 'default_include_holidays')->get();
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
    });
});
