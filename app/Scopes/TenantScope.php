<?php

namespace App\Scopes;

use App\Managers\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Si el modelo no tiene tenant_id, no aplicar el scope
        if (! in_array('tenant_id', $model->getFillable())) {
            return;
        }

        $tenantManager = app(TenantManager::class);
        $tenantId = $tenantManager->getTenantId();

        if ($tenantId) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
