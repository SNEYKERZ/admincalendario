# CHECKLIST DE IMPLEMENTACIÓN - JEFE DE ÁREA + SISTEMA DE CORREOS

**Objetivo:** Implementar aprobación jerárquica con notificaciones por correo  
**Tiempo estimado:** 4-5 semanas  
**Equipos:** 2 desarrolladores  

---

## SEMANA 1: BASE DE DATOS

### Paso 1.1: Crear Migraciones

- [ ] Crear migración: `php artisan make:migration add_area_manager_to_users`
```php
// database/migrations/XXXX_add_area_manager_to_users.php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_area_manager')->default(false)->after('role');
    $table->foreignId('managed_area_id')
        ->nullable()
        ->references('id')
        ->on('areas')
        ->onDelete('set null');
});
```

- [ ] Crear migración: `php artisan make:migration add_manager_to_areas`
```php
Schema::table('areas', function (Blueprint $table) {
    $table->foreignId('area_manager_id')
        ->nullable()
        ->references('id')
        ->on('users')
        ->onDelete('set null');
});
```

- [ ] Crear migración: `php artisan make:migration create_absence_approval_chains`
```php
Schema::create('absence_approval_chains', function (Blueprint $table) {
    $table->id();
    $table->foreignId('absence_id')->constrained()->onDelete('cascade');
    $table->tinyInteger('approval_level')->default(1);
    $table->foreignId('assigned_to')->constrained('users')->onDelete('restrict');
    $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
    $table->text('notes')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->unique(['absence_id', 'approval_level']);
    $table->index(['assigned_to', 'status']);
});
```

- [ ] Crear migración: `php artisan make:migration create_absence_audits`
```php
Schema::create('absence_audits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('absence_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('restrict');
    $table->string('action');
    $table->json('changes')->nullable();
    $table->text('reason')->nullable();
    $table->ipAddress()->nullable();
    $table->timestamps();
    
    $table->index(['absence_id', 'created_at']);
});
```

- [ ] Crear migración: `php artisan make:migration add_audit_fields_to_absences`
```php
Schema::table('absences', function (Blueprint $table) {
    $table->text('rejection_reason')->nullable()->after('status');
    $table->text('internal_notes')->nullable()->after('rejection_reason');
});
```

### Paso 1.2: Ejecutar Migraciones

- [ ] `php artisan migrate` en LOCAL
- [ ] Validar tablas creadas: `php artisan tinker` → inspect DB
- [ ] Crear backup: `mysqldump admincalendar_db > backup_pre_changes.sql`
- [ ] Commit cambios: `git add database/migrations && git commit -m "feat: add approval chain tables"`

---

## SEMANA 2: MODELOS Y RELACIONES

### Paso 2.1: Actualizar Modelo User

- [ ] Abrir `app/Models/User.php`
- [ ] Agregar campos a `$fillable`:
```php
'is_area_manager',
'managed_area_id',
```
- [ ] Agregar casts:
```php
'is_area_manager' => 'boolean',
```
- [ ] Agregar relaciones:
```php
public function managedArea(): BelongsTo
{
    return $this->belongsTo(Area::class, 'managed_area_id');
}

public function approvalChains(): HasMany
{
    return $this->hasMany(AbsenceApprovalChain::class, 'assigned_to');
}

public function isAreaManager(): bool
{
    return $this->is_area_manager;
}
```
- [ ] Test: `php artisan tinker` → `User::first()->managedArea`

### Paso 2.2: Actualizar Modelo Area

- [ ] Abrir `app/Models/Area.php`
- [ ] Agregar campos a `$fillable`:
```php
'area_manager_id',
```
- [ ] Agregar relaciones:
```php
public function manager(): BelongsTo
{
    return $this->belongsTo(User::class, 'area_manager_id');
}

public function approvals(): HasMany
{
    return $this->hasMany(AbsenceApprovalChain::class);
}

public function hasManager(): bool
{
    return $this->area_manager_id !== null;
}
```
- [ ] Test: `php artisan tinker` → `Area::first()->manager`

### Paso 2.3: Crear Modelo AbsenceApprovalChain

- [ ] Crear: `php artisan make:model AbsenceApprovalChain`
- [ ] Agregar contenido:
```php
// app/Models/AbsenceApprovalChain.php
protected $fillable = [
    'absence_id',
    'approval_level',
    'assigned_to',
    'status',
    'notes',
    'completed_at',
];

const LEVEL_AREA_MANAGER = 1;
const LEVEL_ADMIN = 2;

public function absence(): BelongsTo
{
    return $this->belongsTo(Absence::class);
}

public function assignedTo(): BelongsTo
{
    return $this->belongsTo(User::class, 'assigned_to');
}

public function isPending(): bool
{
    return $this->status === 'pendiente';
}
```
- [ ] Test: `AbsenceApprovalChain::first()`

### Paso 2.4: Crear Modelo AbsenceAudit

- [ ] Crear: `php artisan make:model AbsenceAudit`
- [ ] Agregar contenido:
```php
protected $fillable = [
    'absence_id',
    'user_id',
    'action',
    'changes',
    'reason',
    'ip_address',
];

protected $casts = [
    'changes' => 'array',
];

public function absence(): BelongsTo
{
    return $this->belongsTo(Absence::class);
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```
- [ ] Test: `AbsenceAudit::first()`

### Paso 2.5: Actualizar Modelo Absence

- [ ] Abrir `app/Models/Absence.php`
- [ ] Agregar relaciones:
```php
public function approvalChains(): HasMany
{
    return $this->hasMany(AbsenceApprovalChain::class);
}

public function audits(): HasMany
{
    return $this->hasMany(AbsenceAudit::class);
}

public function getApprovalChainStatus(): ?AbsenceApprovalChain
{
    return $this->approvalChains()
        ->where('status', 'pendiente')
        ->orderBy('approval_level')
        ->first();
}
```
- [ ] Test: `Absence::first()->approvalChains()`

### Paso 2.6: Commit

- [ ] `git add app/Models && git commit -m "feat: add approval chain and audit models"`

---

## SEMANA 3: SERVICIOS

### Paso 3.1: Crear ApprovalChainService

- [ ] Crear: `php artisan make:service ApprovalChainService`
- [ ] Copiar código de `ANALISIS_PROYECTO.md` sección 3.1
- [ ] Implementar métodos:
  - [ ] `createApprovalChain(Absence)` - crear cadena automática
  - [ ] `approve(AbsenceApprovalChain, User, string)` - procesar aprobación
  - [ ] `reject(AbsenceApprovalChain, User, string)` - procesar rechazo
  - [ ] `finalizeApproval(Absence, User)` - aprobar finalmente
- [ ] Unit tests:
```php
// tests/Unit/Services/ApprovalChainServiceTest.php
test('crear cadena con jefe de area', function () {
    $area = Area::create(['name' => 'IT', 'area_manager_id' => User::factory()->create()->id]);
    $user = User::factory()->create(['area_id' => $area->id]);
    $absence = Absence::factory()->create(['user_id' => $user->id]);
    
    $service = app(ApprovalChainService::class);
    $service->createApprovalChain($absence);
    
    expect($absence->approvalChains)->toHaveCount(1);
    expect($absence->approvalChains->first()->assigned_to)->toBe($area->area_manager_id);
});
```

### Paso 3.2: Crear AuditService

- [ ] Crear: `php artisan make:service AuditService`
- [ ] Copiar código de `ANALISIS_PROYECTO.md` sección 3.2
- [ ] Implementar:
  - [ ] `logAction(Absence, string, array, string)` - registrar cambio
- [ ] Tests

### Paso 3.3: Actualizar AbsenceService

- [ ] Abrir `app/Services/AbsenceService.php`
- [ ] Inyectar nuevos servicios en constructor:
```php
public function __construct(
    protected VacationService $vacationService,
    protected AbsenceCalculationService $absenceCalculationService,
    protected ApprovalChainService $approvalChainService,
    protected AuditService $auditService,
    protected TenantManager $tenantManager,
) {}
```
- [ ] Actualizar método `create()`:
```php
// Cambio 1: NO aprobar automáticamente si es admin
$status = AbsenceStatus::PENDING; // Cambio aquí

// Cambio 2: Crear cadena de aprobación
$this->approvalChainService->createApprovalChain($absence);

// Cambio 3: Auditoría
$this->auditService->logAction($absence, 'created', [
    'total_days' => $absence->total_days,
]);

// Cambio 4: Notificar primer aprobador
$firstChain = $absence->approvalChains()->first();
if ($firstChain) {
    $firstChain->assignedTo->notify(
        new AbsencePendingApproval($absence, $firstChain)
    );
}
```
- [ ] Actualizar método `approve()` (ver código en ANALISIS)
- [ ] Actualizar método `reject()` (ver código en ANALISIS)
- [ ] Tests de integración

### Paso 3.4: Commit

- [ ] `git add app/Services && git commit -m "feat: add approval chain and audit services"`

---

## SEMANA 4: CONTROLLERS, ROUTES, POLICIES

### Paso 4.1: Crear ApprovalController

- [ ] Crear: `php artisan make:controller ApprovalController`
- [ ] Copiar código de `ANALISIS_PROYECTO.md` sección 5.1
- [ ] Implementar endpoints:
  - [ ] `GET /approvals/pending` - lista para este usuario
  - [ ] `POST /approvals/{chainId}/approve` - aprobar
  - [ ] `POST /approvals/{chainId}/reject` - rechazar
  - [ ] `GET /absences/{id}/history` - historial auditoría
- [ ] Tests:
```php
// tests/Feature/ApprovalControllerTest.php
test('jefe de area ve ausencias pendientes', function () {
    $manager = User::factory()->create(['is_area_manager' => true]);
    $area = Area::factory()->create(['area_manager_id' => $manager->id]);
    $user = User::factory()->create(['area_id' => $area->id]);
    $absence = Absence::factory()->create(['user_id' => $user->id]);
    
    $this->actingAs($manager)->get('/api/approvals/pending')
        ->assertJsonCount(1, 'data');
});
```

### Paso 4.2: Crear AreaManagerController

- [ ] Crear: `php artisan make:controller AreaManagerController`
- [ ] Copiar código de `ANALISIS_PROYECTO.md` sección 5.3
- [ ] Implementar:
  - [ ] `PUT /areas/{id}/manager` - asignar jefe
  - [ ] `DELETE /areas/{id}/manager` - remover jefe

### Paso 4.3: Actualizar AbsenceController

- [ ] Abrir `app/Http/Controllers/AbsenceController.php`
- [ ] Reemplazar método `approve()` (ver ANALISIS)
- [ ] Reemplazar método `reject()` (ver ANALISIS)
- [ ] Actualizar método `store()` para validar área asignada

### Paso 4.4: Crear Policies

- [ ] Crear: `php artisan make:policy AbsenceApprovalChainPolicy`
```php
public function approve(User $user, AbsenceApprovalChain $chain): bool
{
    return $chain->assigned_to === $user->id && $chain->isPending();
}

public function reject(User $user, AbsenceApprovalChain $chain): bool
{
    return $chain->assigned_to === $user->id && $chain->isPending();
}
```
- [ ] Registrar en `AuthServiceProvider`

### Paso 4.5: Actualizar Rutas

- [ ] Abrir `routes/web.php`
- [ ] Agregar rutas de aprobación:
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/approvals/pending', [ApprovalController::class, 'pending']);
    Route::post('/approvals/{chain}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{chain}/reject', [ApprovalController::class, 'reject']);
    Route::get('/absences/{absence}/history', [ApprovalController::class, 'history']);
    Route::put('/areas/{area}/manager', [AreaManagerController::class, 'setManager']);
    Route::delete('/areas/{area}/manager', [AreaManagerController::class, 'removeManager']);
});
```

### Paso 4.6: Tests

- [ ] `php artisan test tests/Feature/ApprovalControllerTest.php`
- [ ] `php artisan test tests/Unit/Services/`

### Paso 4.7: Commit

- [ ] `git add app/Http && routes && git commit -m "feat: add approval endpoints and authorization"`

---

## SEMANA 4-5: NOTIFICACIONES

### Paso 5.1: Crear Notificaciones

- [ ] Crear: `php artisan make:notification AbsencePendingApproval`
```php
// Copiar código de ANALISIS_PROYECTO.md sección 4.1
```
- [ ] Crear: `php artisan make:notification AbsenceApproved`
- [ ] Crear: `php artisan make:notification AbsenceRejected`

### Paso 5.2: Configurar Queue

- [ ] Abrir `.env`:
```env
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com (o tu proveedor)
MAIL_PORT=587
MAIL_USERNAME=tu-email@company.com
MAIL_PASSWORD=tu-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=rh@company.com
MAIL_FROM_NAME="RH - Admin Ausencias"
```

- [ ] Asegurar tabla `jobs` existe:
```bash
php artisan queue:table
php artisan migrate
```

### Paso 5.3: Crear Comando para Procesar Cola

- [ ] Crear: `php artisan make:command ProcessAbsenceNotifications`
```php
// Copiar código de ANALISIS_PROYECTO.md sección 4.3
```

### Paso 5.4: Testing de Emails

- [ ] Usar `Mail::fake()` en tests:
```php
test('notificar jefe de area cuando hay solicitud', function () {
    Mail::fake();
    
    $manager = User::factory()->create();
    $area = Area::factory()->create(['area_manager_id' => $manager->id]);
    $user = User::factory()->create(['area_id' => $area->id]);
    
    $this->actingAs($user)->post('/absences', [
        'absence_type_id' => 1,
        'start_datetime' => now(),
        'end_datetime' => now()->addDay(),
    ]);
    
    Mail::assertSent(AbsencePendingApproval::class);
});
```

### Paso 5.5: Commit

- [ ] `git add app/Notifications app/Console && git commit -m "feat: add email notifications"`

---

## SEMANA 5: FRONTEND (Vue 3)

### Paso 6.1: Crear Componentes

- [ ] Crear `resources/js/Pages/Approvals.vue`
```vue
<!-- Copiar código de ANALISIS_PROYECTO.md sección 6.1 -->
```

- [ ] Crear `resources/js/components/ApproveModal.vue`
```vue
<!-- Copiar código de ANALISIS_PROYECTO.md sección 6.2 -->
```

- [ ] Crear `resources/js/components/RejectModal.vue`
```vue
<!-- Similar a ApproveModal -->
```

### Paso 6.2: Actualizar Dashboard

- [ ] En `resources/js/Pages/Dashboard.vue`, agregar widget:
```vue
<PendingApprovalsWidget :count="pendingApprovalsCount" />
```

### Paso 6.3: Crear Ruta

- [ ] Abrir `resources/js/Pages/` → actualizar rutas:
```php
// routes/web.php
Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
```

### Paso 6.4: Actualizar Navegación

- [ ] Agregar link en sidebar/navbar a `/approvals`

### Paso 6.5: Testing

- [ ] Navegar a `/approvals` en navegador
- [ ] Verificar que carga datos
- [ ] Hacer approve/reject
- [ ] Verificar correo en log

### Paso 6.6: Commit

- [ ] `git add resources/js && git commit -m "feat: add approval UI"`

---

## SEMANA 5+: TESTING Y FIXES

### Paso 7.1: Testing Completo

- [ ] [ ] Tests unitarios de servicios
```bash
php artisan test tests/Unit/Services/ --coverage
```

- [ ] [ ] Tests de controllers
```bash
php artisan test tests/Feature/ApprovalControllerTest.php
```

- [ ] [ ] Tests de validación
```bash
php artisan test tests/Feature/AbsenceValidationTest.php
```

- [ ] [ ] Tests de notificaciones
```bash
php artisan test tests/Feature/NotificationTest.php
```

- [ ] [ ] Coverage >= 70%

### Paso 7.2: Testing Manual (QA)

**Escenario 1: Empleado solicita, jefe aprueba**
- [ ] Login como empleado
- [ ] Solicitar ausencia
- [ ] Verificar que jefe recibe email
- [ ] Login como jefe de área
- [ ] Ir a /approvals
- [ ] Ver ausencia pendiente
- [ ] Aprobar con comentario
- [ ] Verificar que empleado recibe email aprobación

**Escenario 2: Empleado solicita, jefe rechaza**
- [ ] Repetir paso anterior hasta rechazo
- [ ] Rechazar con motivo
- [ ] Verificar email de rechazo al empleado
- [ ] Verificar que vacaciones se restauraron

**Escenario 3: Auditoría**
- [ ] Login como admin
- [ ] Click en ausencia
- [ ] Click en "Ver Historial"
- [ ] Ver cronología: created → pending → rejected
- [ ] Ver IP, usuario, motivo

### Paso 7.3: Performance

- [ ] Verificar índices en BD:
```sql
SHOW INDEXES FROM absence_approval_chains;
```

- [ ] Lazy load de relaciones:
```php
Absence::with(['approvalChains', 'audits'])->get();
```

### Paso 7.4: Documentación

- [ ] Actualizar README.md con nuevas features
- [ ] Agregar diagrama en comentarios de código
- [ ] Documentar nuevos endpoints en Postman

### Paso 7.5: Fixes

- [ ] Investigar cualquier test que falle
- [ ] Reportar y fijar bugs antes de merge

---

## ANTES DE DEPLOY A PRODUCCIÓN

### Checklist Pre-Deploy

- [ ] Todos los tests pasan: `php artisan test`
- [ ] Coverage >= 70%: `php artisan test --coverage`
- [ ] Sin SQL errors: `php artisan migrate:status`
- [ ] Sin PHP warnings: `php -l app/`
- [ ] Migraciones testeadas en staging
- [ ] Backup de BD: `mysqldump admincalendar_db > backup_produccion.sql`
- [ ] Rollback plan documentado
- [ ] Notificación al equipo de RH
- [ ] Validación de correos en producción

### Deploy Checklist

- [ ] Crear rama: `git checkout -b deploy/approval-system`
- [ ] Merge a main: `git merge deploy/approval-system`
- [ ] Tag: `git tag -a v2.1.0 -m "Add approval chain system"`
- [ ] Push: `git push origin main --tags`
- [ ] Ejecutar migraciones:
```bash
php artisan migrate --env=production
```
- [ ] Limpiar cache:
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```
- [ ] Verificar en producción
- [ ] Notificar usuarios

---

## POST-DEPLOY

### Week 1

- [ ] Monitorear logs de errores
- [ ] Responder tickets de usuarios
- [ ] Verificar volumen de correos
- [ ] Ajustar queue workers si es necesario

### Week 2

- [ ] Recopilar feedback
- [ ] Documentar issues encontrados
- [ ] Planear próximo sprint (Incapacidades)

---

## DEFINICIÓN DE "HECHO"

✅ Checklist completo  
✅ Todos los tests pasan  
✅ Coverage >= 70%  
✅ Código revisado (code review)  
✅ Documentación actualizada  
✅ Desplegado a producción  
✅ Usuarios pueden usarlo sin help  
✅ Cero bugs críticos  

---

**Versión:** 1.0  
**Última actualización:** 12 de Junio de 2026  
**Mantenedor:** Equipo de Desarrollo  
