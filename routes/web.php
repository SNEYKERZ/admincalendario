<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\VacationYearController;
use App\Models\AbsenceType;

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


/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    /*
    |--------------------------------------------------------------------------
    | Absences
    |--------------------------------------------------------------------------
    */
    Route::prefix('absences')->group(function () {

        Route::get('/', [AbsenceController::class, 'index'])->name('absences.index');
        Route::post('/', [AbsenceController::class, 'store'])->name('absences.store');
        Route::get('/{absence}', [AbsenceController::class, 'show'])->name('absences.show');

        Route::post('/{absence}/approve', [AbsenceController::class, 'approve'])
            ->name('absences.approve');

        Route::post('/{absence}/reject', [AbsenceController::class, 'reject'])
            ->name('absences.reject');

        Route::delete('/{absence}', [AbsenceController::class, 'destroy'])
            ->name('absences.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Absence Types (para Vue)
    |--------------------------------------------------------------------------
    */
    Route::get('/absence-types', function () {
        return AbsenceType::select('id', 'name')->get();
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

});