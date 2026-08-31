<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminAudit extends Model
{
    protected $fillable = [
        'superadmin_id',
        'impersonated_user_id',
        'tenant_id',
        'action',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function superadmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superadmin_id');
    }

    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeForSuperAdmin($query, $superadminId)
    {
        return $query->where('superadmin_id', $superadminId);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeImpersonations($query)
    {
        return $query->whereIn('action', ['start_impersonation', 'end_impersonation']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    public static function log(
        User $superadmin,
        string $action,
        ?string $description = null,
        ?User $impersonatedUser = null,
        ?Tenant $tenant = null,
        ?array $changes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): static {
        return static::create([
            'superadmin_id' => $superadmin->id,
            'impersonated_user_id' => $impersonatedUser?->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
