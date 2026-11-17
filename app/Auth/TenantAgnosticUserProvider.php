<?php

namespace App\Auth;

use App\Scopes\TenantScope;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Proveedor de usuarios que ignora el TenantScope.
 *
 * El tenant de la petición se deduce del propio usuario autenticado, así que
 * cargar ese usuario (sesión, "recordarme", credenciales) no puede depender
 * del filtro por tenant: con el scope activo, un usuario sin tenant_id quedaba
 * fuera de la consulta, la sesión no se recuperaba y el login rebotaba a
 * /login sin mostrar ningún error.
 */
class TenantAgnosticUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->withoutGlobalScope(TenantScope::class);
    }
}
