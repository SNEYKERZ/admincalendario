# 🏗️ ANÁLISIS ARQUITECTÓNICO: SUPER ADMINISTRADOR EN ARQUITECTURA MULTITENANT

**Documento:** Análisis y plan de reestructuración  
**Fecha:** 2026-08-31  
**Objetivo:** Separar correctamente la responsabilidad del Super Administrador del contexto multitenant

---

## ÍNDICE

1. [A. ARQUITECTURA ACTUAL](#a-arquitectura-actual)
2. [B. PROBLEMAS IDENTIFICADOS](#b-problemas-identificados)
3. [C. ARQUITECTURA PROPUESTA](#c-arquitectura-propuesta)
4. [D. ARCHIVOS A MODIFICAR](#d-archivos-a-modificar)
5. [E. RIESGOS](#e-riesgos)
6. [F. PLAN DE IMPLEMENTACIÓN](#f-plan-de-implementación)

---

## A. ARQUITECTURA ACTUAL

### A.1 Visión General del Sistema

```
┌──────────────────────────────────────────────────────┐
│           PLATAFORMA: GESTIÓN DE AUSENCIAS Y RR.HH   │
├──────────────────────────────────────────────────────┤
│                                                      │
│  ARQUITECTURA: Multitenant con Single Database       │
│  ├─ BASE DE DATOS: 1 única (MySQL/PostgreSQL)       │
│  ├─ AISLAMIENTO: Row-level (tenant_id en tablas)    │
│  └─ DOMINIO: *.ausentra.com (ej: comidas.ausentra.com) │
│                                                      │
│  USUARIOS:                                           │
│  ├─ Super Administrador (creador/propietario)        │
│  ├─ Administrador por Empresa (admin@empresa)        │
│  └─ Colaboradores/Usuarios (usuarios finales)        │
│                                                      │
│  DATOS SEPARADOS POR:                                │
│  ├─ tenant_id en tablas (users, absences, areas...)  │
│  └─ WHERE tenant_id = X en todas las queries         │
│                                                      │
└──────────────────────────────────────────────────────┘
```

### A.2 ¿Cómo se Separan los Datos?

**No hay múltiples bases de datos. Hay UNA sola base de datos.**

Separación de datos por tenant:

```sql
-- TABLA TENANTS (registra empresas)
tenants
├─ id: 1
├─ name: "Comidas S.A."
├─ slug: "comidas"
├─ domain: null (usa comidas.ausentra.com)
└─ is_active: true

-- TABLA USERS (cada usuario pertenece a un tenant)
users
├─ id: 10
├─ name: "María Elena"
├─ email: "maria@comidas.com"
├─ tenant_id: 1  ← ¡AQUÍ! Relacionado a Comidas
├─ role: "admin"
└─ ...

-- TABLA ABSENCES (cada ausencia pertenece a un tenant)
absences
├─ id: 50
├─ user_id: 10  (María)
├─ tenant_id: 1  ← ¡AQUÍ! Separado por tenant
├─ status: "pendiente"
└─ ...

-- CUANDO CONSULTAS:
SELECT * FROM users WHERE tenant_id = 1;
-- ↑ Trae SOLO usuarios de Comidas

SELECT * FROM absences WHERE tenant_id = 1;
-- ↑ Trae SOLO ausencias de Comidas
```

**TenantScope (Global Scope):** Automáticamente agrega `WHERE tenant_id = X` a TODAS las queries.

### A.3 Resolución del Tenant por Request

```
Usuario accede a: comidas.ausentra.com
    ↓
Middleware IdentifyTenant ejecuta
    ↓
TenantManager::resolveFromRequest():
    1. ¿Subdomain? (comidas.ausentra.com → extrae "comidas")
    2. ¿Buscar tenant por slug "comidas"? ✓ Encontrado
    3. Asignar: tenant_id = 1 (Comidas)
    ↓
Para este request, todas las queries usan: WHERE tenant_id = 1
    ↓
TenantManager cacheado en singleton para el request
```

**Ejemplo de flujo:**

```
1. User inicia sesión en comidas.ausentra.com
   → Identificado como admin de Comidas
   → tenant_id resuelto = 1

2. Va a /dashboard
   → GET /dashboard
   → TenantScope: WHERE tenant_id = 1
   → Ve datos SOLO de Comidas

3. Va a /absences
   → GET /absences
   → TenantScope: WHERE tenant_id = 1
   → Ve ausencias SOLO de Comidas

4. Va a /admin/users
   → GET /admin/users
   → TenantScope: WHERE tenant_id = 1
   → Ve usuarios SOLO de Comidas
```

### A.4 Roles y Autorización

**Enum de Roles** (`app/Enums/UserRole.php`):
```php
SUPERADMIN   → Propietario del sistema
ADMIN        → Administrador de una empresa/tenant
COLLABORATOR → Usuario regular de una empresa
```

**Campos de User:**
- `role` - UserRole enum
- `tenant_id` - FK a qué empresa pertenece
- `is_admin()` - True si role == ADMIN o SUPERADMIN
- `isSuperAdmin()` - True si role == SUPERADMIN

**Gates (Autorización Global):**
```php
Gate::define('admin', fn($user) => $user->isAdmin());
Gate::define('superadmin', fn($user) => $user->isSuperAdmin());
```

**Uso en rutas:**
```php
Route::middleware(['auth', 'can:admin'])->group(...);      // Solo admins
Route::middleware(['auth', 'can:superadmin'])->group(...); // Solo superadmin
```

### A.5 Modelos con Tenantable

Estos modelos **heredan automáticamente** el filtrado por tenant_id:

- `User`, `Area`, `Absence`, `AbsenceType`
- `CompanySettings`, `Role`, `VacationYear`
- `HrDocument`, `Subscription`, etc.

Todos tienen:
- Trait `Tenantable` aplicado
- Campo `tenant_id` en la tabla
- Automáticamente filtrados por `TenantScope`

### A.6 Cómo Funciona Actualmente el SuperAdmin

**Problema:** El SuperAdmin está integrado en la lógica de tenant como si fuera un usuario normal.

```
SuperAdmin inicia sesión en ausentra.com
    ↓
¿Qué tenant se resuelve?
    - No hay "superadmin.ausentra.com"
    - No hay dominio customizado
    - Fallback: Main Tenant (¿cuál es?)
    ↓
SuperAdmin asignado a tenant_id = ??? 
    ↓
Va a /dashboard
    → Ve dashboard del tenant X
    → Puede ver/crear ausencias en tenant X
    ↓
¿Pero qué pasa si quiere:
    - ¿Ver datos de otro tenant?
    - ¿Crear un nuevo tenant?
    - ¿Impersonar un admin?
    - ¿Ver estadísticas globales?
    ↓
❌ NO HAY FORMA CLARA
```

---

## B. PROBLEMAS IDENTIFICADOS

### B.1 Confusión Conceptual

| Pregunta | Respuesta Actual | Problema |
|----------|-----------------|----------|
| ¿A qué empresa pertenece el SuperAdmin? | A un tenant (¿cuál?) | No debería pertenecer a ninguna |
| ¿Qué ve el SuperAdmin en /dashboard? | Dashboard del tenant X | Debería ver plataforma global |
| ¿Puede el SuperAdmin crear ausencias? | Sí (pero ¿para qué empresa?) | NO debería poder hacerlo directamente |
| ¿Puede ver datos de múltiples tenants? | Solo el suyo | Debería poder ver todos |
| ¿Existe página de gestión de tenants? | NO | FALTA |
| ¿Puede "entrar como" admin de empresa? | NO | FALTA (impersonation) |

### B.2 Problemas Operacionales

**1. Creación de Ausencias:**
```
SuperAdmin va a: /absences/create
    ↓
¿Qué usuario selecciona? (lista filtrada a su tenant)
¿Qué empresa es? (su tenant actual)
    ↓
Conceptualmente INCORRECTO:
- SuperAdmin NO debería crear ausencias
- Una ausencia debe pertenecer a una empresa específica
- SuperAdmin no pertenece a ninguna empresa
```

**2. Vista de Usuarios:**
```
SuperAdmin va a: /admin/users
    ↓
Query: SELECT * FROM users WHERE tenant_id = X AND role != 'superadmin'
    ↓
Ve SOLO usuarios de su tenant X
    ↓
¿Qué pasa si SuperAdmin accede a trabajos.ausentra.com?
    ↓
Cambio de tenant_id en sesión
    ↓
Ahora ve usuarios de empresa "Trabajos"
    ↓
Pero ¿cuál es la verdadera empresa del SuperAdmin? CONFUSIÓN
```

**3. Falta Visión Global:**
```
NO existe forma de ver:
- ¿Cuántos tenants hay?
- ¿Cuáles están activos?
- ¿Cuáles están suspendidos?
- ¿Cuántos usuarios en total?
- ¿Cuántas ausencias en toda la plataforma?
- ¿Estado de salud de cada tenant?
```

**4. Sin Impersonation:**
```
SuperAdmin NO puede:
- "Entrar como" Admin de Comidas
- Ver exactamente qué ve ese admin
- Diagnosticar qué está roto
- Revisar configuración de empresa
- Simular experiencia del usuario
```

### B.3 Problemas de Seguridad

| Problema | Riesgo | Severidad |
|----------|--------|-----------|
| Ambigüedad de tenant_id para SuperAdmin | Acceso a datos equivocados | 🔴 CRÍTICA |
| No hay separación "SuperAdmin" vs "Admin" | SuperAdmin podría ser degradado a Admin | 🔴 CRÍTICA |
| Falta auditoría de cambios SuperAdmin | No hay trazabilidad | 🔴 ALTA |
| TenantScope se puede bypasear con withoutTenant() | Exposición de datos | 🟡 MEDIA |
| No hay validación de tenant_id en controllers | SQL injection conceptual | 🟡 MEDIA |

### B.4 Problemas Técnicos

**1. Single Database pero sin separación clara:**
- TenantScope filtra automáticamente
- Pero SuperAdmin necesita poder ver "todos los datos"
- Necesita usar `withoutTenant()` explícitamente
- Propenso a errores de seguridad

**2. Roles como Enum vs per-Tenant:**
- User.role es enum string (SUPERADMIN, ADMIN, COLLABORATOR)
- Role model es per-tenant
- Posible inconsistencia

**3. Tenant Resolution Dinámico:**
- Resuelto en cada request basado en dominio/subdomain
- No hay concepto de "sesión sin tenant"
- Dificulta impersonation controlado

---

## C. ARQUITECTURA PROPUESTA

### C.1 Principios Fundamentales

```
┌─────────────────────────────────────────────────────────┐
│         SUPER ADMINISTRADOR (GLOBAL)                    │
│                                                         │
│  • NO pertenece operativamente a ningún tenant          │
│  • NO tiene tenant_id fijo en su User record            │
│  • Ve la plataforma completa                            │
│  • Gestiona tenants (crear, activar, suspender)         │
│  • Puede impersonar admins de tenants temporalmente      │
│  • NO puede hacer operaciones de tenant sin impersonation│
│                                                         │
└─────────────────────────────────────────────────────────┘
                          │
              ┌───────────┼───────────┐
              │           │           │
         ┌────▼──────┐ ┌──▼─────────┐ ┌──▼──────────┐
         │ TENANT A  │ │ TENANT B   │ │ TENANT C    │
         │ (Comidas) │ │ (Trabajos) │ │ (Empresa X) │
         ├───────────┤ ├────────────┤ ├─────────────┤
         │ • Admin   │ │ • Admin    │ │ • Admin     │
         │ • Usuarios│ │ • Usuarios │ │ • Usuarios  │
         │ • Áreas   │ │ • Áreas    │ │ • Áreas     │
         │ • Datos   │ │ • Datos    │ │ • Datos     │
         └───────────┘ └────────────┘ └─────────────┘
```

### C.2 Concepto de "Contexto"

**Contexto 1: SuperAdmin Global (Defecto)**

```
Usuario Autenticado:
├─ real_user: SuperAdmin
├─ impersonated_user: null
├─ tenant_id: null (SIN TENANT)
└─ effective_role: 'superadmin'

Puede Ver:
✓ Dashboard global (estadísticas de plataforma)
✓ Listado de todos los tenants
✓ Información de cada tenant
✓ Estadísticas agregadas
✓ Auditoría de acciones

NO Puede Ver:
✗ Usuarios específicos de tenant
✗ Ausencias de tenant
✗ Operaciones dentro de tenant

NO Puede Hacer:
✗ Crear ausencia (a qué empresa?)
✗ Crear usuario (en qué empresa?)
✗ Editar área (de qué empresa?)
```

**Contexto 2: SuperAdmin Impersonando Admin**

```
Usuario Autenticado:
├─ real_user: SuperAdmin (la identidad real)
├─ impersonated_user: admin@comidas.com
├─ tenant_id: 1 (Comidas)
└─ effective_role: 'admin' (ve como admin)

Puede Ver:
✓ TODO lo que ve el admin de Comidas
✓ Dashboard de Comidas
✓ Usuarios de Comidas
✓ Ausencias de Comidas
✓ Áreas de Comidas

Puede Hacer:
✓ Las mismas operaciones que el admin
  (pero sabe que es SuperAdmin impersonando)

Auditoría registra:
→ SuperAdmin X impersonó a Admin Y
→ En tenant Z
→ Desde [hora] a [hora]
→ Acciones realizadas durante impersonation
```

**Contexto 3: Salir de Impersonation**

```
SuperAdmin hace clic en "Salir"
    ↓
Vuelve a:
├─ real_user: SuperAdmin
├─ impersonated_user: null
├─ tenant_id: null (SIN TENANT)
└─ effective_role: 'superadmin'
    ↓
Regresa a Dashboard Global
```

### C.3 Cambios en Modelos

**User Model:**

```php
// app/Models/User.php

protected $fillable = [
    // ... campos existentes
    'is_superadmin_only',  // ← NUEVO CAMPO
];

protected $casts = [
    'role' => UserRole::class,
    'tenant_id' => 'integer',
    'is_superadmin_only' => 'boolean', // ← NUEVO
];

// Métodos existentes mejorados:
public function isSuperAdmin(): bool
{
    return $this->role === UserRole::SUPERADMIN;
}

// Métodos nuevos:
public function isSuperAdminOnly(): bool
{
    return $this->is_superadmin_only === true;
}

public function belongsToTenant(): bool
{
    // True si es usuario operativo de un tenant
    return $this->tenant_id !== null && !$this->is_superadmin_only;
}
```

**SuperAdminContext (En Sesión):**

```php
// Guardado en: session('super_admin_context')
// Estructura:
[
    'real_user_id' => 1,           // SuperAdmin que inició sesión
    'impersonated_user_id' => null, // null si no impersonando
    'tenant_id' => null,            // null si en contexto global
    'effective_role' => 'superadmin',
    'started_at' => '2026-08-31 10:30:00',
]

// Cuando impersona:
[
    'real_user_id' => 1,
    'impersonated_user_id' => 42,    // Admin de Comidas
    'tenant_id' => 1,                 // ID del tenant Comidas
    'effective_role' => 'admin',      // Ve como admin
    'started_at' => '2026-08-31 10:35:00',
]
```

**SuperAdminAudit Model (NUEVO):**

```php
// app/Models/SuperAdminAudit.php

SuperAdminAudit
├─ id
├─ superadmin_id (FK → User)
├─ action: enum ['login', 'start_impersonation', 'end_impersonation', 'create_tenant', ...]
├─ impersonated_user_id: nullable (FK → User)
├─ tenant_id: nullable (FK → Tenant)
├─ description: string (qué pasó)
├─ changes: JSON (qué se modificó)
├─ ip_address: string
├─ user_agent: string
├─ created_at
└─ updated_at

// Audita:
- Cuándo SuperAdmin inicia sesión
- Cuándo comienza impersonation
- Cuándo termina impersonation
- Qué acciones realizó (cambios en datos)
- De dónde (IP)
```

### C.4 Cambios en Middleware

**SuperAdminContextMiddleware (NUEVO):**

```php
// app/Http/Middleware/SuperAdminContextMiddleware.php

public function handle(Request $request, Closure $next)
{
    // Solo aplica a SuperAdmin
    if (!auth()->user()?->isSuperAdmin()) {
        return $next($request);
    }

    // Cargar/validar contexto de impersonation
    $context = session('super_admin_context');

    if (!$context) {
        // Contexto global por defecto
        session(['super_admin_context' => [
            'real_user_id' => auth()->id(),
            'impersonated_user_id' => null,
            'tenant_id' => null,
            'effective_role' => 'superadmin',
            'started_at' => now(),
        ]]);
        return $next($request);
    }

    // Si está impersonando, validar que sea válido
    if ($context['impersonated_user_id']) {
        $impersonated = User::find($context['impersonated_user_id']);
        
        if (!$impersonated || 
            $impersonated->tenant_id !== $context['tenant_id'] ||
            !$impersonated->isAdmin()) {
            // Impersonation inválida, limpiar
            session()->forget('super_admin_context');
            return redirect('/superadmin/dashboard')
                ->with('error', 'Impersonation expirada o inválida');
        }
    }

    // Hacer contexto disponible en request
    $request->attributes->set('super_admin_context', $context);

    return $next($request);
}
```

**Modificar IdentifyTenant Middleware:**

```php
// app/Http/Middleware/IdentifyTenant.php

public function handle(Request $request, Closure $next)
{
    // Si es SuperAdmin, verificar contexto
    if (auth()->user()?->isSuperAdmin()) {
        $context = session('super_admin_context');
        
        if (!$context['impersonated_user_id']) {
            // Contexto global: NO establecer tenant
            // Las queries usarán withoutTenant() o equivalente
            return $next($request);
        }
        
        // Si impersonando: establecer tenant de impersonation
        app(TenantManager::class)->setTenant($context['tenant_id']);
        return $next($request);
    }

    // Lógica normal para Admin/Colaborador
    // ... (resolver tenant por dominio/subdomain como siempre)
}
```

### C.5 Nuevas Rutas SuperAdmin

```php
// routes/web.php

Route::middleware(['auth', 'can:superadmin'])->prefix('superadmin')->group(function () {
    
    // Dashboard Global
    Route::get('/dashboard', 'SuperAdmin\DashboardController@index')
        ->name('superadmin.dashboard');
    
    // Gestión de Tenants
    Route::get('/tenants', 'SuperAdmin\TenantController@index')
        ->name('superadmin.tenants.index');
    Route::get('/tenants/{tenant}', 'SuperAdmin\TenantController@show')
        ->name('superadmin.tenants.show');
    Route::post('/tenants/{tenant}/activate', 'SuperAdmin\TenantController@activate')
        ->name('superadmin.tenants.activate');
    Route::post('/tenants/{tenant}/deactivate', 'SuperAdmin\TenantController@deactivate')
        ->name('superadmin.tenants.deactivate');
    Route::post('/tenants/{tenant}/suspend', 'SuperAdmin\TenantController@suspend')
        ->name('superadmin.tenants.suspend');
    
    // Impersonation
    Route::post('/impersonate/{user}', 'SuperAdmin\ImpersonationController@start')
        ->name('superadmin.impersonate.start');
    Route::post('/impersonate/stop', 'SuperAdmin\ImpersonationController@stop')
        ->name('superadmin.impersonate.stop');
    
    // Auditoría
    Route::get('/audit', 'SuperAdmin\AuditController@index')
        ->name('superadmin.audit.index');
    
    // Gestión de Cuentas Globales
    Route::get('/accounts', 'SuperAdmin\AccountController@index')
        ->name('superadmin.accounts.index');
});
```

### C.6 Nueva Página: SuperAdmin Dashboard

```
URL: /superadmin/dashboard

┌─────────────────────────────────────────────────────┐
│  PANEL SUPER ADMINISTRADOR                          │
│  Dashboard Global de la Plataforma                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│  📊 ESTADÍSTICAS DE PLATAFORMA                      │
│  ┌─────────────────────────────────────────────┐   │
│  │  Tenants Activos: 5      Suspendidos: 1    │   │
│  │  Total de Usuarios: 142                     │   │
│  │  Total de Ausencias: 1,234                  │   │
│  │  Ausencias por Aprobar: 23                  │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  🏢 TENANTS (últimos 5)                             │
│  ┌─────────────────────────────────────────────┐   │
│  │ Empresa    │ Dominio          │ Estado     │   │
│  │────────────┼──────────────────┼────────────│   │
│  │ Comidas    │ comidas.ausentra │ ✓ Activo   │   │
│  │ Trabajos   │ trabajos.ausentra│ ✓ Activo   │   │
│  │ Empresa X  │ empresa-x.com    │ ✗ Inactivo │   │
│  │ [Ver más]                                  │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  📋 AUDITORÍA (últimas acciones)                    │
│  ├─ 2026-08-31 14:30 - Login                       │
│  └─ 2026-08-31 14:35 - Impersonó: admin@comidas   │
│                                                     │
│  [Gestionar Tenants] [Ver Auditoría] [Cuentas]    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### C.7 Nueva Página: Gestión de Tenants

```
URL: /superadmin/tenants

┌───────────────────────────────────────────────────────┐
│  GESTIÓN DE TENANTS                                   │
├───────────────────────────────────────────────────────┤
│                                                       │
│  LISTADO DE EMPRESAS/TENANTS                          │
│  ┌──────────────────────────────────────────────────┐ │
│  │ Empresa     │ Dominio          │ Estado │ Admin   │ │
│  ├─────────────────────────────────────────────────┤ │
│  │ Comidas     │ comidas.ausentra │ Activo │ María   │ │
│  │ [Ver]  [Impersonar como Admin]  [Suspender]    │ │
│  │                                                 │ │
│  │ Trabajos    │ trabajos.ausentra│ Activo │ Carlos  │ │
│  │ [Ver]  [Impersonar como Admin]  [Suspender]    │ │
│  │                                                 │ │
│  │ Empresa X   │ empresa-x.com    │ Pausado│ Juan    │ │
│  │ [Ver]  [Impersonar como Admin]  [Activar]      │ │
│  │                                                 │ │
│  └──────────────────────────────────────────────────┘ │
│                                                       │
│  [+ Crear Nuevo Tenant]                              │
│                                                       │
└───────────────────────────────────────────────────────┘
```

**Cuando hace clic en [Impersonar como Admin]:**

```
1. SuperAdmin selecciona tenant
2. Sistema busca admin del tenant
3. Inicia impersonation:
   - Crea SuperAdminAudit (start_impersonation)
   - session['super_admin_context'] con datos de impersonation
4. Redirect a /dashboard
5. SuperAdmin ve EXACTAMENTE lo que ve el admin
6. Navbar muestra: "Visualizando como: maria@comidas.com [Salir]"
7. Todas las queries filtradas a tenant_id = 1
```

**Cuando hace clic en [Salir]:**

```
1. SuperAdmin hace clic en "Salir de Impersonation"
2. POST /superadmin/impersonate/stop
3. Crea SuperAdminAudit (end_impersonation)
4. session()->forget('super_admin_context')
5. Redirect a /superadmin/dashboard
6. Regresa a contexto global
7. tenant_id = null en todas las queries
```

---

## D. ARCHIVOS A MODIFICAR

### D.1 CREAR (Nuevos)

```
app/Models/SuperAdminAudit.php
  └─ Modelo para auditoría de acciones SuperAdmin

app/Http/Middleware/SuperAdminContextMiddleware.php
  └─ Middleware que valida/carga contexto de impersonation

app/Http/Controllers/SuperAdmin/DashboardController.php
  └─ Dashboard global con estadísticas de plataforma

app/Http/Controllers/SuperAdmin/TenantController.php
  └─ CRUD de tenants (index, show, activate, deactivate, suspend)

app/Http/Controllers/SuperAdmin/ImpersonationController.php
  └─ Inicio y fin de impersonation

app/Http/Controllers/SuperAdmin/AuditController.php
  └─ Visualización de logs de SuperAdmin

app/Http/Controllers/SuperAdmin/AccountController.php
  └─ Gestión global de cuentas

resources/js/Pages/SuperAdmin/Dashboard.vue
  └─ Dashboard SuperAdmin (Vue)

resources/js/Pages/SuperAdmin/Tenants.vue
  └─ Página de gestión de tenants

resources/js/Pages/SuperAdmin/Tenants/Show.vue
  └─ Detalle de tenant

resources/js/Pages/SuperAdmin/Audit.vue
  └─ Página de auditoría

resources/js/composables/useSuperAdminContext.ts
  └─ Composable para manejar contexto de impersonation

database/migrations/2026_08_31_add_superadmin_fields_to_users.php
  └─ Agregar is_superadmin_only a users table

database/migrations/2026_08_31_create_super_admin_audits_table.php
  └─ Crear tabla de auditoría
```

### D.2 MODIFICAR (Existentes)

```
app/Models/User.php
  ├─ Agregar is_superadmin_only campo
  ├─ Agregar isSuperAdminOnly() método
  ├─ Agregar belongsToTenant() método
  └─ Clarificar isSuperAdmin()

app/Models/Tenant.php
  ├─ Agregar relación superAdminAudits()
  └─ Agregar método para obtener admin principal

app/Scopes/TenantScope.php
  ├─ Agregar lógica para SuperAdmin en contexto global
  ├─ Si SuperAdmin sin impersonation → NO aplicar scope
  └─ Si SuperAdmin impersonando → aplicar scope normal

app/Http/Middleware/IdentifyTenant.php
  ├─ Agregar lógica para SuperAdmin
  ├─ Si contexto global → NO establecer tenant_id
  └─ Si impersonando → establecer tenant de impersonation

app/Http/Kernel.php
  └─ Registrar SuperAdminContextMiddleware en middleware stack

routes/web.php
  ├─ Agregar rutas /superadmin/*
  └─ Modificar rutas existentes si es necesario

app/Providers/AuthServiceProvider.php
  └─ Actualizar gates si es necesario

app/Http/Controllers/DashboardController.php
  ├─ Detectar si es SuperAdmin en contexto global
  └─ Redirigir a /superadmin/dashboard

app/Http/Controllers/Admin/UserController.php
  ├─ Bloquear acceso a SuperAdmin en contexto global
  └─ Verificar tenant_id en políticas

resources/js/Layouts/AppLayout.vue
  ├─ Agregar navbar diferente para SuperAdmin
  └─ Mostrar badge de impersonation si aplica

resources/js/app.js (o HandleInertiaRequests.php)
  └─ Compartir super_admin_context con frontend
```

---

## E. RIESGOS

### E.1 Seguridad

| Riesgo | Probabilidad | Severidad | Mitigación |
|--------|-------------|-----------|-----------|
| Impersonation no se valida correctamente | Media | 🔴 CRÍTICA | Validar en middleware |
| Contexto global permite acceso a datos de tenant | Alta | 🔴 CRÍTICA | Auditar withoutTenant() |
| Session de impersonation no se limpia | Baja | 🔴 CRÍTICA | Middleware limpia en logout |
| SuperAdmin ve datos de otro tenant accidentalmente | Media | 🟡 ALTA | Validar en controllers |
| TenantScope se bypasea sin validación | Alta | 🟡 ALTA | Auditar todos withoutTenant() |

### E.2 Compatibilidad

| Componente | Problema | Probabilidad | Solución |
|-----------|----------|-------------|----------|
| Composables Vue | Esperan tenant_id resuelto | Alta | Actualizar para manejar contexto global |
| Controllers Admin | Asumen tenant_id siempre | Alta | Agregar validaciones |
| Políticas | Basadas en tenant_id | Media | Compatibles, validar SuperAdmin |
| Queries | Filtradas automáticamente | Baja | TenantScope maneja ambos casos |

### E.3 Base de Datos

| Riesgo | Descripción | Impacto |
|--------|------------|--------|
| Migrations | Agregar columnas a users | Bajo (reversible) |
| Datos inconsistentes | SuperAdmin con/sin tenant_id | Bajo (manejable) |
| Performance | Full scans si no hay índices | Bajo (se pueden agregar) |

---

## F. PLAN DE IMPLEMENTACIÓN

### Fase 1: Preparación (1-2 días)

**1.1 Auditar código existente**
- [ ] Encontrar todos los usos de `withoutTenant()`
- [ ] Identificar queries que asumen tenant_id
- [ ] Mapear rutas que necesitan cambios
- [ ] Listar componentes Vue que usan tenant_id

**1.2 Setup**
- [ ] Crear rama: `feature/superadmin-architecture`
- [ ] Crear plan de rollback

### Fase 2: Backend - Modelos & Migraciones (2-3 días)

**2.1 Crear modelos**
- [ ] SuperAdminAudit.php
- [ ] Modificar User.php (agregar campos/métodos)
- [ ] Modificar Tenant.php (relaciones)

**2.2 Migraciones**
- [ ] add_superadmin_fields_to_users.php
- [ ] create_super_admin_audits_table.php
- [ ] Ejecutar migraciones
- [ ] Crear índices de performance

### Fase 3: Backend - Middleware (1-2 días)

**3.1 Crear/modificar middleware**
- [ ] SuperAdminContextMiddleware.php
- [ ] Modificar IdentifyTenant.php
- [ ] Registrar en Kernel.php

**3.2 Testing**
- [ ] Unit tests para TenantScope
- [ ] Unit tests para SuperAdminContextMiddleware
- [ ] Tests de validación de impersonation

### Fase 4: Backend - Controllers (2-3 días)

**4.1 Controllers SuperAdmin**
- [ ] DashboardController
- [ ] TenantController
- [ ] ImpersonationController
- [ ] AuditController
- [ ] AccountController (opcional)

**4.2 Modificar controllers existentes**
- [ ] DashboardController (redirigir SuperAdmin)
- [ ] Admin UserController (bloquear SuperAdmin global)
- [ ] Validaciones en otros controllers

### Fase 5: Routes (1 día)

**5.1 Routes**
- [ ] Agregar grupo /superadmin/*
- [ ] Validar protección con middleware
- [ ] Testing manual de rutas

### Fase 6: Frontend - Pages (2-3 días)

**6.1 Crear Vue pages**
- [ ] SuperAdmin/Dashboard.vue
- [ ] SuperAdmin/Tenants.vue
- [ ] SuperAdmin/Tenants/Show.vue
- [ ] SuperAdmin/Audit.vue

**6.2 Composables**
- [ ] useSuperAdminContext.ts
- [ ] useImpersonation.ts (opcional)

**6.3 Layouts**
- [ ] Modificar AppLayout.vue
- [ ] Agregar navbar para SuperAdmin
- [ ] Badge de impersonation

### Fase 7: Composables en Paralelo (3-4 días)

- [ ] useTableState.ts
- [ ] useLoadingState.ts
- [ ] useFormSubmit.ts
- [ ] useApiCall.ts
- [ ] useCRUD.ts
- [ ] useModalState.ts

Refactorizar componentes priority-high:
- [ ] Documents.vue
- [ ] Calendar.vue
- [ ] Dashboard.vue

### Fase 8: Testing (2-3 días)

**8.1 Unit Tests**
- [ ] TenantScope behavior
- [ ] SuperAdminContextMiddleware
- [ ] ImpersonationController

**8.2 Integration Tests**
- [ ] Flujo completo SuperAdmin
- [ ] Flujo impersonation
- [ ] Validaciones de seguridad

**8.3 Manual Testing**
- [ ] Checklist de validaciones (ver abajo)

### Fase 9: Documentación & Release (1 día)

- [ ] Actualizar README
- [ ] Documentar API nuevos endpoints
- [ ] Crear guía de SuperAdmin
- [ ] Merge a main
- [ ] Tag release

---

## G. VALIDACIONES OBLIGATORIAS POST-IMPLEMENTACIÓN

### G.1 SuperAdmin

- [ ] Inicia sesión correctamente
- [ ] Ve Dashboard Global (NO dashboard de tenant)
- [ ] NO tiene tenant_id asignado en contexto global
- [ ] NO puede crear ausencias desde contexto global
- [ ] NO puede crear usuarios desde contexto global
- [ ] Puede ver listado de todos los tenants
- [ ] Puede activar/desactivar tenants
- [ ] Puede seleccionar un tenant para impersonar
- [ ] Puede impersonar admin de tenant
- [ ] Mientras impersona ve datos SOLO del tenant
- [ ] Navbar muestra "Visualizando como: admin@tenant [Salir]"
- [ ] Puede salir de impersonation
- [ ] Al salir vuelve a contexto global
- [ ] Auditoría registra todas las acciones

### G.2 Admin (Regresión)

- [ ] Inicia sesión correctamente
- [ ] Ve su dashboard (de su tenant)
- [ ] Ve solo usuarios de su tenant
- [ ] Puede crear/editar usuarios
- [ ] Puede crear/editar ausencias
- [ ] Puede crear/editar áreas
- [ ] NO ve datos de otros tenants
- [ ] NO puede impersonar otros usuarios

### G.3 Colaborador/Usuario (Regresión)

- [ ] Inicia sesión correctamente
- [ ] Ve su dashboard personal
- [ ] Puede crear ausencias propias
- [ ] Puede ver ausencias propias
- [ ] NO ve ausencias de otros usuarios
- [ ] NO ve datos de otros tenants

### G.4 Seguridad

- [ ] SuperAdmin en contexto global NO puede acceder a /admin/users
- [ ] Queries en contexto global usan withoutTenant() explícitamente
- [ ] Queries en contexto impersonado están filtradas a tenant
- [ ] withoutTenant() solo accesible para SuperAdmin
- [ ] Impersonation expira si se invalida
- [ ] Logout limpia contexto de impersonation
- [ ] IP address registrada en auditoría

### G.5 Multitenancy

```
Tenant A (comidas.ausentra.com)
  ├─ Query: SELECT * FROM users → Solo de Comidas
  └─ Query: SELECT * FROM absences → Solo de Comidas

Tenant B (trabajos.ausentra.com)
  ├─ Query: SELECT * FROM users → Solo de Trabajos
  └─ Query: SELECT * FROM absences → Solo de Trabajos

SuperAdmin (contexto global)
  ├─ Query: SELECT * FROM users → ERROR (sin withoutTenant())
  └─ Query: SELECT * FROM users.withoutTenant() → Todos (OK)
```

---

## CONCLUSIÓN

Esta arquitectura propuesta:

✅ Separa claramente SuperAdmin (plataforma) vs Admin (tenant)  
✅ Permite impersonation controlado para debugging  
✅ Audita todas las acciones de SuperAdmin  
✅ Mantiene seguridad mediante validaciones explícitas  
✅ Es compatible con arquitectura existente  
✅ No requiere cambios en DB schema principales  
✅ Escalable para futuros casos de uso  

**Siguiente paso:** Aprobación del plan → Comenzar Fase 1
