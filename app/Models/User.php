<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Tenantable;

    protected $fillable = [
        'tenant_id',
        'name',
        'first_name',
        'last_name',
        'identification',
        'gender',
        'phone',
        'email',
        'password',
        'role',
        'is_superadmin_only',
        'is_active',
        'birth_date',
        'hire_date',
        'photo_path',
        'area_id',
        'is_area_manager',
        'managed_area_id',
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
        'is_active' => 'boolean',
        'is_area_manager' => 'boolean',
        'is_superadmin_only' => 'boolean',
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

    public function area(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function vacationYears(): HasMany
    {
        return $this->hasMany(VacationYear::class);
    }

    public function approvedAbsences(): HasMany
    {
        return $this->hasMany(Absence::class, 'approved_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(HrDocument::class);
    }

    public function managedArea(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Area::class, 'managed_area_id');
    }

    public function approvalChains(): HasMany
    {
        return $this->hasMany(AbsenceApprovalChain::class, 'assigned_to');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AbsenceAudit::class);
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [UserRole::ADMIN->value, UserRole::SUPERADMIN->value]);
    }

    public function scopeRegularAdmins($query)
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
            : in_array($this->role, [UserRole::ADMIN->value, UserRole::SUPERADMIN->value]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role instanceof UserRole
            ? $this->role === UserRole::SUPERADMIN
            : $this->role === UserRole::SUPERADMIN->value;
    }

    public function isColaborador(): bool
    {
        return $this->role instanceof UserRole
            ? $this->role === UserRole::COLLABORATOR
            : $this->role === UserRole::COLLABORATOR->value;
    }

    public function isAreaManager(): bool
    {
        return (bool) $this->is_area_manager;
    }

    public function isSuperAdminOnly(): bool
    {
        return $this->is_superadmin_only === true;
    }

    public function belongsToTenant(): bool
    {
        return $this->tenant_id !== null && !$this->is_superadmin_only;
    }

    /*
    |--------------------------------------------------------------------------
    | MODULE ACCESS
    |--------------------------------------------------------------------------
    */

    public function canAccessModule(string $moduleSlug): bool
    {
        // SuperAdmin global (sin tenant) tiene acceso a todo excepto módulos de tenant
        if ($this->isSuperAdmin() && !$this->tenant_id) {
            return false; // SuperAdmin global no accede a módulos normales
        }

        // User debe ser admin y tener tenant
        if (!$this->isAdmin() || !$this->tenant_id) {
            return false;
        }

        // Verificar módulo
        $tenant = $this->tenant;
        return $tenant && $tenant->hasModule($moduleSlug);
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
        if (! $this->photo_path) {
            return asset('no-pic.jpg');
        }

        if (! Storage::disk('public')->exists($this->photo_path)) {
            return asset('no-pic.jpg');
        }

        return asset('storage/'.$this->photo_path);
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
