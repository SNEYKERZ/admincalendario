<?php

namespace App\Models\Concerns;

use App\Managers\TenantManager;
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

        // Si el código que crea el registro no especificó tenant_id, lo
        // completamos con el tenant actual. Sin esto, un registro creado
        // sin tenant_id explícito queda con tenant_id = NULL y el
        // TenantScope lo deja invisible para siempre en las próximas
        // consultas (aunque exista en la base de datos).
        static::creating(function ($model) {
            if (empty($model->tenant_id) && in_array('tenant_id', $model->getFillable())) {
                $tenantId = app(TenantManager::class)->getTenantId();

                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
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
