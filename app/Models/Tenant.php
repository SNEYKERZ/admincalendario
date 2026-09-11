<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\OvertimeHours;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'logo',
        'email',
        'phone',
        'address',
        'identification',
        'timezone',
        'locale',
        'is_active',
        'is_main',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySettings::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function absenceTypes(): HasMany
    {
        return $this->hasMany(AbsenceType::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function hrDocuments(): HasMany
    {
        return $this->hasMany(HrDocument::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function superAdminAudits(): HasMany
    {
        return $this->hasMany(SuperAdminAudit::class);
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
    }

    public function overtimeHours(): HasMany
    {
        return $this->hasMany(OvertimeHours::class);
    }

    /*
    |--------------------------------------------------------------------------
    | MODULE & PLAN METHODS
    |--------------------------------------------------------------------------
    */

    public function getCurrentSubscription(): ?Subscription
    {
        // Primero intentar obtener suscripción activa y no expirada
        $subscription = $this->subscriptions()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if ($subscription) {
            return $subscription;
        }

        // Si no hay suscripción activa, retornar la más reciente (para debugging/fallback)
        return $this->subscriptions()
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    public function getCurrentPlanModules(): \Illuminate\Support\Collection
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription) {
            return collect();
        }

        return $subscription->plan->modules;
    }

    public function hasModule(string $moduleSlug): bool
    {
        return $this->getCurrentPlanModules()
            ->where('slug', $moduleSlug)
            ->isNotEmpty();
    }

    public function getEnabledModules(): array
    {
        return $this->getCurrentPlanModules()
            ->pluck('slug')
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function getDomainUrlAttribute(): ?string
    {
        if ($this->domain) {
            return 'https://'.$this->domain;
        }

        return config('app.tenant_url_template')
            ? str_replace('{slug}', $this->slug, config('app.tenant_url_template'))
            : null;
    }

    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)->first();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
