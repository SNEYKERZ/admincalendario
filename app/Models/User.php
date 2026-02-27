<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'birth_date',
        'hire_date',
        'photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'email_verified_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function vacationYears(): HasMany
    {
        return $this->hasMany(VacationYear::class);
    }

    public function approvedAbsences(): HasMany
    {
        return $this->hasMany(Absence::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeColaboradores($query)
    {
        return $query->where('role', 'colaborador');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isColaborador(): bool
    {
        return $this->role === 'colaborador';
    }

    /*
    |--------------------------------------------------------------------------
    | VACATION LOGIC
    |--------------------------------------------------------------------------
    */

    public function availableVacationDays(): float
    {
        return $this->vacationYears()
            ->where('expires_at', '>=', now())
            ->get()
            ->sum(function ($year) {
                return $year->allocated_days - $year->used_days;
            });
    }
}