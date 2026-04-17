<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Scopes\TenantScope;

trait Tenantable
{
    /**
     * Boot the trait.
     *
     * Agrega el TenantScope globalmente a todos los queries del modelo.
     */
    public static function bootTenantable(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the tenant ID attribute.
     */
    public function getTenantIdAttribute(): ?int
    {
        return $this->attributes['tenant_id'] ?? null;
    }

    /**
     * Relación con el tenant.
     */
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para omitir el TenantScope (para superadmin o queries globales).
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScopes();
    }

    /**
     * Scope para especificar un tenant específico.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
