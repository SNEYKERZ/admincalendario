<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'identification',
        'phone',
        'email',
        'password',
        'role',
        'birth_date',
        'hire_date',
        'photo_path',
    ];

    protected $appends = [
        'photo_url',
        'avatar',
        'role_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'email_verified_at' => 'datetime',
        'role' => UserRole::class,
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
        return $query->where('role', UserRole::ADMIN->value);
    }

    public function scopeColaboradores($query)
    {
        return $query->where('role', UserRole::COLLABORATOR->value);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role instanceof UserRole
            ? $this->role->isAdmin()
            : $this->role === UserRole::ADMIN->value;
    }

    public function isColaborador(): bool
    {
        return $this->role instanceof UserRole
            ? $this->role === UserRole::COLLABORATOR
            : $this->role === UserRole::COLLABORATOR->value;
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

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path
            ? asset('storage/'.$this->photo_path)
            : null;
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->photo_url;
    }

    public function getRoleNameAttribute(): string
    {
        return $this->role instanceof UserRole
            ? $this->role->label()
            : ($this->role === 'admin' ? 'Administrador' : 'Colaborador');
    }
}
