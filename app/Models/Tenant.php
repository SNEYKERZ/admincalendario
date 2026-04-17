<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public function subscription(): HasMany
    {
        return $this->hasMany(Subscription::class);
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
