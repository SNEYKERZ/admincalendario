<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\VacationYearController;
use App\Http\Controllers\VacationController;
use App\Models\AbsenceType;
use App\Models\User;

require __DIR__ . '/settings.php';
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

    Route::get('/', function () {return auth()->check() ? redirect('/dashboard') : redirect('/login'); });
    // routes/api.php
    Route::get('/me', function () { return auth()->user();});
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('/gestion-usuarios', 'GestionUsuarios')->name('gestion-usuarios');
    Route::get('/gestion-usuarios/data', [VacationController::class, 'index'])->name('gestion-usuarios.data');
    Route::post('/gestion-usuarios/{user}/adjust', [VacationController::class, 'adjust'])->name('gestion-usuarios.adjust');

    Route::get('/users-list', function () {
        return User::with(['vacationYears' => function ($q) {
            $q->where('expires_at', '>=', now());}])->get()->map(function ($user) 
            {

            $available = $user->vacationYears->sum(function ($year) {
                return $year->allocated_days - $year->used_days;
            });

            return [
                'id' => $user->id,
                'name' => $user->name,
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
        Route::delete('/{absence}', [AbsenceController::class, 'destroy'])->name('absences.destroy');
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
