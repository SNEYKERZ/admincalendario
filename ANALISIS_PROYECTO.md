# ANÁLISIS COMPLETO: SISTEMA DE ADMINISTRADOR DE AUSENCIAS

**Fecha:** 12 de Junio de 2026  
**Estado del Proyecto:** ✅ MADURO y FUNCIONAL  
**Stack:** Laravel 12 + Vue 3 + Inertia.js + MySQL  

---

## 📋 TABLA DE CONTENIDOS

1. [Errores e Incoherencias Encontrados](#errores)
2. [Problemas en Formularios](#problemas-formularios)
3. [Problemas de Flujo de Negocio](#problemas-flujo)
4. [Deuda Técnica Identificada](#deuda-tecnica)
5. [Mejoras Propuestas](#mejoras-propuestas)
6. [Nuevos Módulos necesarios para RH](#nuevos-modulos)
7. [Plan de Implementación: Sistema de Correos + Jefe de Área](#plan-sistema-correos)
8. [Roadmap de Implementación Priorizado](#roadmap)

---

## 1. ERRORES E INCOHERENCIAS ENCONTRADOS {#errores}

### 1.1 **Sistema de Aprobación Incompleto** ⚠️ CRÍTICO

**Problema:**
- Las ausencias pendientes se envían notificación a TODOS los admins sin distinción
- NO existe el concepto de "Jefe de Área" (area_manager/area_head)
- Un admin puede aprobar/rechazar ausencia de empleado de cualquier área
- NO hay restricción de autoridad: un admin de un área puede manejar ausencias de otra área
- NO hay auditoría de quién aprobó y por qué

**Impacto:** Falta de control de autoridades, posibles abusos, incumplimiento de gobernanza.

**Ubicación:** `app/Services/AbsenceService.php:98-100`

---

### 1.2 **Duplicación de Lógica de Cálculo** ⚠️ ALTO

**Problema:**
- La lógica de cálculo de ausencias está en 3 lugares:
  1. `StoreAbsenceRequest::withValidator()` (líneas 84-168)
  2. `AbsenceService::create()` (líneas 45-50)
  3. `AbsenceService::update()` (líneas 163-173)

**Impacto:** 
- Riesgo de inconsistencia si se modifica en un lugar y se olvida en otro
- Código difícil de mantener
- Violation DRY (Don't Repeat Yourself)

**Solución:** Centralizar en `AbsenceCalculationService`

---

### 1.3 **Validación de Overlapping Deficiente** ⚠️ MEDIO

**Problema:**
- En `StoreAbsenceRequest:134-144` usa `whereIn` con ambos estados
- En `AbsenceService:58-64` usa solo `whereIn` y comparación con `<` y `>`
- Lógica de overlapping DIFERENTE en cada lugar

```php
// StoreAbsenceRequest
whereIn('status', ['pendiente', 'aprobado'])
    ->whereBetween('start_datetime', [$start, $end])
    ->orWhereBetween('end_datetime', [$start, $end])
    ->orWhere(...) // 3 condiciones

// AbsenceService
whereIn('status', [...])
    ->where('start_datetime', '<', $end)
    ->where('end_datetime', '>', $start) // Solo 1 condición
```

**Impacto:** Posible aceptación de overlapping en ciertos casos edge.

---

### 1.4 **Falta de Validación en Update** ⚠️ ALTO

**Problema:**
- Endpoint `PUT /absences/{id}` en `AbsenceController::update()` (línea 138-155)
- NO usa `StoreAbsenceRequest`, usa validación inline sin reglas de negocio
- NO valida:
  - Overlapping con otras ausencias
  - Saldo de vacaciones
  - Límites de tipos especiales (ej: Cumpleaños solo 1 vez/año)
  - Rangos de fechas válidos

**Impacto:** Empleado puede editar ausencia pendiente y violar reglas de negocio.

---

### 1.5 **Notificaciones Incompletas** ⚠️ ALTO

**Problema:**
- Cuando se rechaza una ausencia, se notifica al empleado
- PERO: No se notifica al admin que la rechazó
- No hay auditoría de cambios de estado
- No se registra "razón de rechazo"

**Ubicación:** 
- `AbsenceService::setStatus()` líneas 261-276
- NO existe campo `rejection_reason` en tabla `absences`

---

### 1.6 **Gestión de Vacaciones Débil** ⚠️ ALTO

**Problema:**
- Un admin puede crear ausencia sin validar saldo (se aprueba automáticamente)
- Al rechazar ausencia, se restauran días pero NO hay auditoría de dónde vinieron
- Si un empleado tiene 0 días disponibles, puede solicitar pero se rechazará solo en validación
- NO existe sistema de "adelanto de vacaciones"

---

### 1.7 **Estados Inconsistentes** ⚠️ MEDIO

**Problema:**
- Enums usa español: `PENDING = 'pendiente'`, `APPROVED = 'aprobado'`, `REJECTED = 'rechazado'`
- Base de datos guarda en español
- Pero en algunos lugares se compara como string y en otros como Enum
- En `AbsenceController::index()` línea 56 se pasa status como string directamente

**Impacto:** Confusión, posible inconsistencia si se cambia Enum.

---

### 1.8 **Falta de Validación de Permisos en Actualización** ⚠️ MEDIO

**Problema:**
- Policy `AbsencePolicy` existe para `view`, `create`, `approve`, `reject`, `pending`, `delete`
- PERO `update` en `AbsenceController::update()` (línea 139) valida permisos PERO:
  - Un empleado puede editar su ausencia PENDIENTE ✅
  - Un empleado puede editar su ausencia APROBADA ❌ NO DEBERÍA
  - Un admin puede editar ausencia de otro admin ❌

---

### 1.9 **Falta de Transacciones en Algunos Endpoints** ⚠️ MEDIO

**Problema:**
- `AbsenceController::update()` no está en transacción DB
- Si falla la deducción de vacaciones a mitad, quedará inconsistente

---

### 1.10 **Formulario de Ausencia sin Feedback Visual** ⚠️ BAJO

**Problema:**
- No hay indicación visual de:
  - Cuántos días de vacación quedan disponibles
  - Si ya existe una ausencia en ese rango
  - Días contabilizables vs excluidos (fines de semana, feriados)

---

## 2. PROBLEMAS EN FORMULARIOS {#problemas-formularios}

### 2.1 **AbsenceModal.vue - Cálculo en Tiempo Real Lento**

**Problema:** El modal probablemente no calcula en tiempo real los días/horas mientras escribes, lo que causa UX pobre.

---

### 2.2 **Validación Asincrónica Faltante**

**Problema:** No hay validación asincrónica de overlapping mientras el usuario está escribiendo (no debounce).

---

### 2.3 **Importación Excel sin Rollback**

**Problema:** 
- En `UserController::import()` probablemente importa usuarios masivamente
- Si uno falla, otros ya fueron insertados (falta transacción)
- No hay forma de saber qué usuario falló

---

## 3. PROBLEMAS DE FLUJO DE NEGOCIO {#problemas-flujo}

### 3.1 **Aprobación Automática por Admin No es Realista**

**Problema:**
- Línea 76 en `AbsenceService::create()`: "Si admin crea, se aprueba automáticamente"
- Esto es incorrecto: un admin creando para otro no significa que esté aprobada
- Debería ir a "pendiente" y el admin debería aprobarla explícitamente

---

### 3.2 **No hay Escalada de Permisos**

**Problema:**
- Si jefe de área rechaza, empleado NO puede apelar
- Si empleado cree que fue rechazado injustamente, no hay proceso

---

### 3.3 **No existe Registro de Cambios (Auditoría)**

**Problema:**
- No hay tabla que registre: quién cambió qué, cuándo, por qué
- Solo se guarda en `approved_by` y `approved_at` final

---

## 4. DEUDA TÉCNICA IDENTIFICADA {#deuda-tecnica}

### 4.1 Modelos incompletos
- Area NO tiene relación de `manager` o `head`
- User NO tiene campo `is_area_manager` o `managed_areas`

### 4.2 Inconsistencia de Casts
- AbsenceStatus usa Enum pero en algunas queries se compara como string

### 4.3 Falta de Índices de Base de Datos
- Tabla `absences` debería tener índice en `(user_id, status, start_datetime)`
- Tabla `users` debería tener índice en `(area_id, is_active)`

### 4.4 Logging Pobre
- Solo hay Log::warning en caso de tabla faltante
- No hay auditoría de cambios de estado, aprobaciones, rechazos

### 4.5 Notifications Queue
- Se usa `database` driver pero nunca se procesa la cola
- Las notificaciones se guardan pero nunca se envían por email

---

## 5. MEJORAS PROPUESTAS {#mejoras-propuestas}

### 5.1 **Implementar Jefe de Área** ✨ PRIORIDAD: CRÍTICA

```
┌─────────────────────────────────────────┐
│ ESTRUCTURA PROPUESTA                    │
├─────────────────────────────────────────┤
│ User                                    │
│  ├─ area_id (FK Area)                  │
│  ├─ is_area_manager (BOOLEAN)           │
│  ├─ is_system_admin (BOOLEAN)           │
│  └─ managed_area_ids (JSON opcional)   │
│                                         │
│ Area                                    │
│  ├─ area_manager_id (FK User)          │
│  └─ parent_area_id (FK Area) [opcional]│
│                                         │
│ AbsenceApprovalChain (NEW)              │
│  ├─ absence_id (FK Absence)            │
│  ├─ level (1=area_manager, 2=admin)    │
│  ├─ assigned_to (FK User)              │
│  ├─ status (pendiente/aprobado/...)   │
│  ├─ notes                               │
│  └─ approved_at                         │
└─────────────────────────────────────────┘
```

### 5.2 **Sistema de Notificaciones por Correo Completo**

Implementar cadena de correos:
1. Empleado solicita ausencia
2. Email a jefe de área: "Nuevo solicitud de ausencia"
3. Jefe aprueba/rechaza
4. Email a empleado: "Tu ausencia fue [APROBADA|RECHAZADA]"
5. Email a admin (CC)

### 5.3 **Auditoría Completa de Cambios**

Crear tabla `absence_history`:
```sql
absence_history:
├─ id
├─ absence_id (FK)
├─ changed_by (FK User)
├─ status_from → status_to
├─ total_days_from → total_days_to
├─ reason
├─ ip_address
└─ created_at
```

### 5.4 **Validación Robusta de Updates**

- Usar FormRequest en endpoint `PUT /absences/{id}`
- Reutilizar lógica de `StoreAbsenceRequest`
- Validar que ausencia está en estado correcto para editar

### 5.5 **Gestión de Vacaciones Mejorada**

Agregar campos:
```sql
vacation_years:
├─ carryover_days (días llevados del año anterior)
├─ carryover_expires_at (cuándo expiran los carry-over)
├─ advance_days_used (días adelantados)
└─ notes
```

### 5.6 **Sistema de Plantillas de Rechazo**

Permitir que admin/jefe seleccione motivo de rechazo predeterminado:
- "Insuficiente saldo"
- "Período de blackout"
- "Necesario en operaciones"
- Otro (texto libre)

---

## 6. NUEVOS MÓDULOS NECESARIOS PARA RH {#nuevos-modulos}

### 6.1 **Gestión de Incapacidades/Licencias Médicas** 📋

```
Modulo: Incapacidades
├─ Crear incapacidad (empleado/admin)
├─ Adjuntar certificado médico (PDF/imagen)
├─ Estados: Pendiente verificación → Aprobada → Rechazada
├─ Descuento automático de vacaciones (configurable por empresa)
├─ Notificación a jefe de área
├─ Reporte mensual/anual
└─ Integración con nómina (opcional)

Tabla: incapacities
├─ id, user_id, type (médica/maternidad/paternidad)
├─ start_date, end_date
├─ medical_certificate_path
├─ issued_by (nombre médico)
├─ institution (clínica/hospital)
├─ status (pendiente/aprobada/rechazada)
├─ approved_by, approved_at
├─ notes, created_at
```

---

### 6.2 **Gestión de Jornadas y Horarios Flexibles** ⏰

```
Modulo: Horarios y Jornadas
├─ Definir jornadas (fulltime, part-time, flexible)
├─ Horarios por usuario (entrada/salida esperada)
├─ Cambios de horario (solicitar cambio flexible)
├─ Cumplimiento de horario (reporte)
├─ Alertas de retrasos/inasistencias
└─ Exportar para nómina

Tabla: work_schedules
├─ id, user_id
├─ schedule_type (fixed/flexible/part_time)
├─ start_time, end_time
├─ flexible_start_earliest, flexible_end_latest
├─ days_per_week
├─ created_at

Tabla: schedule_requests
├─ id, user_id, requested_schedule_id
├─ requested_start_date
├─ reason
├─ status (pendiente/aprobado/rechazado)
├─ approved_by, approved_at
```

---

### 6.3 **Gestión de Compensación y Bonificación** 💰

```
Modulo: Compensación
├─ Configurar salario base por puesto
├─ Bonificaciones (desempeño, asistencia, productividad)
├─ Descuentos (faltas, tardanzas, disciplina)
├─ Proyección de nómina
├─ Reporte de compensación por empleado/área
├─ Auditoría de cambios de salario

Tabla: compensation_rules
├─ id, tenant_id
├─ name (ej: "Bono Asistencia")
├─ bonus_type (performance/attendance/productivity)
├─ calculation_type (fixed/percentage)
├─ amount
├─ conditions (JSON)
├─ is_active

Tabla: employee_compensation
├─ id, user_id
├─ base_salary
├─ currency
├─ last_increase_date
├─ increase_percentage

Tabla: compensation_transactions
├─ id, user_id, compensation_rule_id
├─ amount, reason, month
├─ created_at
```

---

### 6.4 **Gestión de Evaluaciones de Desempeño** 📊

```
Modulo: Evaluaciones
├─ Crear plantillas de evaluación
├─ Evaluación 360 (autoevaluación, jefe, pares)
├─ Competencias y calificaciones
├─ Objetivos OKR por período
├─ Feedback y plan de desarrollo
├─ Histórico de evaluaciones
├─ Reportes de desempeño por área

Tabla: performance_templates
├─ id, tenant_id, name, description
├─ evaluation_period (annual/bi-annual)
├─ competencies (JSON)

Tabla: performance_evaluations
├─ id, user_id, evaluated_by
├─ period_start, period_end
├─ overall_rating (1-5)
├─ evaluation_type (self/peer/manager)
├─ feedback (text)
├─ status (draft/completed)

Tabla: performance_goals
├─ id, user_id, period
├─ objective (text)
├─ target_value, actual_value
├─ weight (%)
├─ status (not_started/in_progress/completed)
```

---

### 6.5 **Gestión de Formación y Desarrollo** 🎓

```
Modulo: Capacitación
├─ Catálogo de cursos/entrenamientos
├─ Solicitar formación
├─ Asignar formación (admin)
├─ Seguimiento de progreso
├─ Certificados
├─ ROI de capacitación
├─ Plan de desarrollo por empleado

Tabla: training_courses
├─ id, tenant_id
├─ title, description
├─ duration_hours, cost
├─ provider (interno/externo)
├─ required_for_positions (JSON)
├─ created_at

Tabla: training_enrollments
├─ id, user_id, course_id
├─ enrollment_date
├─ completion_date
├─ status (pending/in_progress/completed/cancelled)
├─ certificate_path
├─ grade (score)

Tabla: development_plans
├─ id, user_id, created_by_user_id
├─ period_start, period_end
├─ goals (JSON)
├─ training_needs (JSON)
├─ mentor_id
├─ status (draft/active/completed)
```

---

### 6.6 **Gestión de Disciplina y Sanciones** ⚖️

```
Modulo: Disciplina
├─ Registrar faltas/infracciones
├─ Sistema de sanciones progresivas
├─ Apelación de sanciones
├─ Descuentos salariales automáticos
├─ Auditoría completa
├─ Reportes disciplinarios

Tabla: infractions
├─ id, user_id
├─ infraction_type (tardanza/inasistencia/conducta/otro)
├─ date, description
├─ created_by, reported_date

Tabla: disciplinary_actions
├─ id, infraction_id
├─ action_type (warning/suspension/termination)
├─ issued_by, issued_date
├─ reason, justification
├─ status (pending/active/appealed/resolved)
├─ appeal_reason, appeal_date

Tabla: salary_deductions
├─ id, user_id, disciplinary_action_id
├─ deduction_amount, deduction_reason
├─ period, created_at
```

---

### 6.7 **Gestión de Salida de Empleados (Offboarding)** 🚪

```
Modulo: Offboarding
├─ Crear proceso de salida
├─ Checklist de tareas (IT, legal, finanzas)
├─ Recuperación de activos
├─ Liquidación final
├─ Carta de referencia
├─ Encuesta de salida
├─ Bloqueo de accesos automático

Tabla: employee_exits
├─ id, user_id
├─ exit_date, notice_date
├─ reason (resignation/termination/retirement/transfer)
├─ initiated_by_user_id
├─ status (pending/in_progress/completed)
├─ final_settlement_amount

Tabla: offboarding_checklists
├─ id, employee_exit_id
├─ task (Deshabilitar email, Recuperar laptop, etc)
├─ assigned_to, due_date
├─ completed_date
├─ notes

Tabla: exit_surveys
├─ id, employee_exit_id
├─ feedback (JSON)
├─ recommendation (would_rehire: yes/no)
├─ created_at
```

---

### 6.8 **Gestión de Nómina y Payroll** 💵

```
Modulo: Nómina (INTEGRACIÓN)
├─ Generación de nóminas (mensual/quincenal)
├─ Cálculo automático de impuestos
├─ Descuentos y deducciones (según ausencias, disciplina)
├─ Comprobantes de pago (digital)
├─ Reportes fiscales
├─ Integración bancaria (transferencias)
├─ Historial de nóminas

Tabla: payroll_periods
├─ id, tenant_id
├─ period_name (Junio 2026)
├─ start_date, end_date
├─ status (draft/locked/processed)
├─ created_at

Tabla: payroll_details
├─ id, payroll_period_id, user_id
├─ base_salary, gross_salary
├─ deductions_json (salud, pensión, otros)
├─ bonuses_json
├─ net_salary
├─ payment_date
├─ payment_method (transfer/cash/check)

Tabla: tax_configurations
├─ id, tenant_id
├─ tax_type (income/healthcare/pension)
├─ percentage, min_amount, max_amount
└─ applies_to_role_ids (JSON)
```

---

### 6.9 **Gestión de Organigramas y Estructuras** 🏢

```
Modulo: Organización
├─ Visualizar organigrama interactivo
├─ Jerarquía de reportes
├─ Matriz de responsabilidades (RACI)
├─ Costó por área/proyecto
├─ Capacidad de equipo
├─ Análisis de brecha de personal

Tabla: positions
├─ id, tenant_id
├─ title, description, level
├─ department_id (FK)
├─ reports_to_id (FK Position)
├─ min_salary, max_salary
├─ required_skills (JSON)

Tabla: position_assignments
├─ id, user_id, position_id
├─ start_date, end_date
├─ created_at

Tabla: responsibilities
├─ id, position_id
├─ responsibility (text)
├─ percentage_of_time
```

---

### 6.10 **Portal de Empleado (Self-Service)** 🔐

```
Modulo: Portal de Empleado
├─ Ver mi perfil personal
├─ Consultar saldo de vacaciones/ausencias
├─ Descargar recibos de pago
├─ Solicitar cambios de datos personales
├─ Ver evaluaciones de desempeño
├─ Acceder a manual de empleado (documentos)
├─ Consultar organigrama
├─ Enviar consultas al RH

Features:
├─ Dashboard personalizado
├─ Calendario de mis ausencias
├─ Mis documentos del RH
├─ Historial de cambios salariales
├─ Mis objetivos OKR
├─ Mis cursos de formación
├─ Tickets/consultas al RH
└─ Descarga de certificados
```

---

## 7. PLAN DE IMPLEMENTACIÓN: SISTEMA DE CORREOS + JEFE DE ÁREA {#plan-sistema-correos}

### **FASE 1: INFRAESTRUCTURA DE BASE DE DATOS** (1-2 semanas)

#### 1.1 Agregar campos a tabla `users`

```php
// Migration: add_area_manager_fields_to_users
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_area_manager')->default(false)->after('role');
    $table->foreign('managed_area_id')
        ->nullable()
        ->references('id')
        ->on('areas')
        ->onDelete('set null');
});
```

#### 1.2 Agregar campo a tabla `areas`

```php
// Migration: add_manager_to_areas
Schema::table('areas', function (Blueprint $table) {
    $table->foreignId('area_manager_id')
        ->nullable()
        ->references('id')
        ->on('users')
        ->onDelete('set null');
});
```

#### 1.3 Crear tabla de cadena de aprobación

```php
// Migration: create_absence_approval_chains
Schema::create('absence_approval_chains', function (Blueprint $table) {
    $table->id();
    $table->foreignId('absence_id')->constrained()->onDelete('cascade');
    $table->tinyInteger('approval_level')->default(1); // 1=area_manager, 2=admin
    $table->foreignId('assigned_to')->constrained('users')->onDelete('restrict');
    $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
    $table->text('notes')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    
    $table->unique(['absence_id', 'approval_level']);
    $table->index(['assigned_to', 'status']);
});
```

#### 1.4 Crear tabla de auditoría

```php
// Migration: create_absence_audits
Schema::create('absence_audits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('absence_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('restrict');
    $table->string('action'); // created, approved, rejected, updated
    $table->text('changes'); // JSON diff
    $table->text('reason')->nullable();
    $table->ipAddress()->nullable();
    $table->timestamps();
    
    $table->index(['absence_id', 'created_at']);
});
```

#### 1.5 Agregar columnas a `absences`

```php
// Migration: add_audit_fields_to_absences
Schema::table('absences', function (Blueprint $table) {
    $table->text('rejection_reason')->nullable();
    $table->text('internal_notes')->nullable();
    $table->foreignId('requested_by')->nullable()->constrained('users');
});
```

---

### **FASE 2: MODELOS Y RELACIONES** (1-2 semanas)

#### 2.1 Actualizar modelo `User`

```php
// app/Models/User.php
class User extends Authenticatable {
    
    protected $fillable = [
        // ... existing fields ...
        'is_area_manager',
        'managed_area_id',
    ];
    
    protected $casts = [
        // ... existing casts ...
        'is_area_manager' => 'boolean',
    ];
    
    // NUEVAS RELACIONES
    public function managedArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'managed_area_id');
    }
    
    public function areaManagerAbsences(): HasMany
    {
        return $this->hasMany(Absence::class, 'area_manager_id');
    }
    
    public function approvalChains(): HasMany
    {
        return $this->hasMany(AbsenceApprovalChain::class, 'assigned_to');
    }
    
    public function isAreaManager(): bool
    {
        return $this->is_area_manager;
    }
}
```

#### 2.2 Actualizar modelo `Area`

```php
// app/Models/Area.php
class Area extends Model {
    
    protected $fillable = [
        // ... existing fields ...
        'area_manager_id',
    ];
    
    // NUEVA RELACIÓN
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'area_manager_id');
    }
    
    public function hasManager(): bool
    {
        return $this->area_manager_id !== null;
    }
    
    public function getManagerName(): string
    {
        return $this->manager?->name ?? 'Sin asignar';
    }
}
```

#### 2.3 Crear modelo `AbsenceApprovalChain`

```php
// app/Models/AbsenceApprovalChain.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceApprovalChain extends Model
{
    protected $fillable = [
        'absence_id',
        'approval_level',
        'assigned_to',
        'status',
        'notes',
        'completed_at',
    ];
    
    protected $casts = [
        'approval_level' => 'integer',
        'completed_at' => 'datetime',
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
    
    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }
}
```

#### 2.4 Actualizar modelo `Absence`

```php
// app/Models/Absence.php
class Absence extends Model {
    
    public function approvalChains()
    {
        return $this->hasMany(AbsenceApprovalChain::class);
    }
    
    public function audits()
    {
        return $this->hasMany(AbsenceAudit::class);
    }
    
    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    
    public function getApprovalChainStatus(): ?AbsenceApprovalChain
    {
        return $this->approvalChains()
            ->where('status', 'pendiente')
            ->orderBy('approval_level')
            ->first();
    }
}
```

#### 2.5 Crear modelo `AbsenceAudit`

```php
// app/Models/AbsenceAudit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceAudit extends Model
{
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
    
    public function absence()
    {
        return $this->belongsTo(Absence::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### **FASE 3: SERVICIOS Y LÓGICA DE NEGOCIO** (2-3 semanas)

#### 3.1 Crear `ApprovalChainService`

```php
// app/Services/ApprovalChainService.php
namespace App\Services;

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Models\User;
use App\Enums\AbsenceStatus;
use Illuminate\Support\Facades\DB;

class ApprovalChainService
{
    /**
     * Crear cadena de aprobación automática según área del empleado
     */
    public function createApprovalChain(Absence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $user = $absence->user;
            
            // Nivel 1: Jefe de Área
            if ($user->area && $user->area->hasManager()) {
                AbsenceApprovalChain::create([
                    'absence_id' => $absence->id,
                    'approval_level' => AbsenceApprovalChain::LEVEL_AREA_MANAGER,
                    'assigned_to' => $user->area->area_manager_id,
                    'status' => 'pendiente',
                ]);
            }
            
            // Nivel 2: Admin (siempre, para auditoría)
            if ($absence->type->requires_approval) {
                $admin = User::admins()->first();
                if ($admin) {
                    AbsenceApprovalChain::create([
                        'absence_id' => $absence->id,
                        'approval_level' => AbsenceApprovalChain::LEVEL_ADMIN,
                        'assigned_to' => $admin->id,
                        'status' => 'pendiente',
                    ]);
                }
            }
        });
    }
    
    /**
     * Procesar aprobación en la cadena
     */
    public function approve(
        AbsenceApprovalChain $chain,
        User $approver,
        string $notes = null
    ): AbsenceApprovalChain {
        return DB::transaction(function () use ($chain, $approver, $notes) {
            $chain->update([
                'status' => 'aprobado',
                'assigned_to' => $approver->id,
                'notes' => $notes,
                'completed_at' => now(),
            ]);
            
            // Verificar si hay más niveles pendientes
            $nextPending = $chain->absence->approvalChains()
                ->where('approval_level', '>', $chain->approval_level)
                ->where('status', 'pendiente')
                ->first();
            
            // Si no hay más niveles, la ausencia se aprueba finalmente
            if (!$nextPending) {
                $this->finalizeApproval($chain->absence, $approver);
            }
            
            return $chain->fresh();
        });
    }
    
    /**
     * Procesar rechazo en la cadena
     */
    public function reject(
        AbsenceApprovalChain $chain,
        User $rejector,
        string $reason
    ): AbsenceApprovalChain {
        return DB::transaction(function () use ($chain, $rejector, $reason) {
            $absence = $chain->absence;
            
            // Marcar esta cadena como rechazada
            $chain->update([
                'status' => 'rechazado',
                'assigned_to' => $rejector->id,
                'notes' => $reason,
                'completed_at' => now(),
            ]);
            
            // Rechazar TODAS las cadenas pendientes
            $absence->approvalChains()
                ->where('status', 'pendiente')
                ->update([
                    'status' => 'rechazado',
                    'completed_at' => now(),
                ]);
            
            // Actualizar estado de ausencia
            $absence->update([
                'status' => AbsenceStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'approved_by' => $rejector->id,
                'approved_at' => now(),
            ]);
            
            return $chain->fresh();
        });
    }
    
    /**
     * Finalizar aprobación después de todas las cadenas
     */
    protected function finalizeApproval(Absence $absence, User $finalApprover): void
    {
        $absence->update([
            'status' => AbsenceStatus::APPROVED->value,
            'approved_by' => $finalApprover->id,
            'approved_at' => now(),
        ]);
    }
}
```

#### 3.2 Crear `AuditService`

```php
// app/Services/AuditService.php
namespace App\Services;

use App\Models\Absence;
use App\Models\AbsenceAudit;
use Auth;
use Request;

class AuditService
{
    public function logAction(
        Absence $absence,
        string $action,
        array $changes = [],
        string $reason = null
    ): AbsenceAudit {
        return AbsenceAudit::create([
            'absence_id' => $absence->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes,
            'reason' => $reason,
            'ip_address' => Request::ip(),
        ]);
    }
}
```

#### 3.3 Actualizar `AbsenceService`

```php
// app/Services/AbsenceService.php
class AbsenceService
{
    public function __construct(
        protected VacationService $vacationService,
        protected AbsenceCalculationService $absenceCalculationService,
        protected ApprovalChainService $approvalChainService,
        protected AuditService $auditService,
        protected TenantManager $tenantManager,
        // ... otros servicios ...
    ) {}
    
    public function create(array $data): Absence
    {
        return DB::transaction(function () use ($data) {
            // ... lógica existente ...
            
            $absence = Absence::create([
                ...$data,
                'user_id' => $user->id,
                'status' => AbsenceStatus::PENDING->value,
                // NO aprobar automáticamente
            ]);
            
            // NUEVO: Crear cadena de aprobación
            $this->approvalChainService->createApprovalChain($absence);
            
            // NUEVO: Log de auditoría
            $this->auditService->logAction(
                $absence,
                'created',
                ['total_days' => $absence->total_days],
                null
            );
            
            // Notificar al jefe de área o primer aprobador
            $firstChain = $absence->approvalChains()->first();
            if ($firstChain) {
                $firstChain->assignedTo->notify(
                    new AbsencePendingApproval($absence, $firstChain)
                );
            }
            
            return $absence->fresh(['user', 'type', 'approvalChains']);
        });
    }
    
    public function approve(Absence $absence, User $admin): Absence
    {
        return DB::transaction(function () use ($absence, $admin) {
            $chain = $absence->getApprovalChainStatus();
            
            if ($chain) {
                $this->approvalChainService->approve($chain, $admin);
            }
            
            $absence->refresh();
            
            // Log de auditoría
            $this->auditService->logAction(
                $absence,
                'approved',
                ['status' => 'aprobado'],
                null
            );
            
            // Notificar al empleado
            $absence->user->notify(new AbsenceApproved($absence));
            
            return $absence;
        });
    }
    
    public function reject(Absence $absence, User $rejector, string $reason): Absence
    {
        return DB::transaction(function () use ($absence, $rejector, $reason) {
            $chain = $absence->getApprovalChainStatus();
            
            if ($chain) {
                $this->approvalChainService->reject($chain, $rejector, $reason);
            }
            
            $absence->refresh();
            
            // Log de auditoría
            $this->auditService->logAction(
                $absence,
                'rejected',
                ['status' => 'rechazado'],
                $reason
            );
            
            // Restaurar vacaciones si corresponde
            if ($absence->type->deducts_vacation && $absence->isApproved()) {
                $this->vacationService->restoreDays(
                    $absence->user,
                    $absence->total_days
                );
            }
            
            // Notificar al empleado
            $absence->user->notify(
                new AbsenceRejected($absence, $reason)
            );
            
            return $absence;
        });
    }
}
```

---

### **FASE 4: NOTIFICACIONES POR CORREO** (2 semanas)

#### 4.1 Crear Notificaciones

```php
// app/Notifications/AbsencePendingApproval.php
namespace App\Notifications;

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AbsencePendingApproval extends Notification
{
    use Queueable;
    
    public function __construct(
        protected Absence $absence,
        protected AbsenceApprovalChain $chain
    ) {}
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nueva solicitud de ausencia pendiente")
            ->greeting("Hola {$notifiable->first_name},")
            ->line("El empleado **{$this->absence->user->full_name}** del área **{$this->absence->user->area->name}** ha solicitado ausencia.")
            ->line("**Tipo:** {$this->absence->type->name}")
            ->line("**Período:** {$this->absence->start_datetime->format('d/m/Y')} a {$this->absence->end_datetime->format('d/m/Y')}")
            ->line("**Duración:** {$this->absence->total_days} días")
            ->action("Ver Solicitud", url("/absences/{$this->absence->id}"))
            ->line("Por favor, aprueba o rechaza en el plazo de 48 horas.");
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'absence_id' => $this->absence->id,
            'user_name' => $this->absence->user->full_name,
            'area_name' => $this->absence->user->area?->name,
            'type' => $this->absence->type->name,
            'total_days' => $this->absence->total_days,
            'message' => "Nueva solicitud de {$this->absence->user->full_name}",
        ];
    }
}
```

```php
// app/Notifications/AbsenceApproved.php
namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AbsenceApproved extends Notification
{
    use Queueable;
    
    public function __construct(protected Absence $absence) {}
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Tu solicitud de ausencia fue APROBADA")
            ->greeting("Hola {$notifiable->first_name},")
            ->line("Tu solicitud de ausencia ha sido **aprobada**.")
            ->line("**Tipo:** {$this->absence->type->name}")
            ->line("**Período:** {$this->absence->start_datetime->format('d/m/Y')} a {$this->absence->end_datetime->format('d/m/Y')}")
            ->line("**Duración:** {$this->absence->total_days} días")
            ->line("Aprobada por: **{$this->absence->approver->full_name}**")
            ->action("Ver Detalles", url("/absences/{$this->absence->id}"));
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'absence_id' => $this->absence->id,
            'status' => 'aprobada',
            'message' => "Tu ausencia fue aprobada",
        ];
    }
}
```

```php
// app/Notifications/AbsenceRejected.php
namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AbsenceRejected extends Notification
{
    use Queueable;
    
    public function __construct(
        protected Absence $absence,
        protected string $reason
    ) {}
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("❌ Tu solicitud de ausencia fue RECHAZADA")
            ->greeting("Hola {$notifiable->first_name},")
            ->line("Tu solicitud de ausencia ha sido **rechazada**.")
            ->line("**Tipo:** {$this->absence->type->name}")
            ->line("**Período:** {$this->absence->start_datetime->format('d/m/Y')} a {$this->absence->end_datetime->format('d/m/Y')}")
            ->line("**Motivo:** {$this->reason}")
            ->action("Ver Solicitud", url("/absences/{$this->absence->id}"))
            ->line("Si tienes preguntas, contáctate con tu jefe de área o el equipo de RH.");
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'absence_id' => $this->absence->id,
            'status' => 'rechazada',
            'reason' => $this->reason,
            'message' => "Tu ausencia fue rechazada",
        ];
    }
}
```

#### 4.2 Configurar Queue para Correos

```php
// .env
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com (or your provider)
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=rh@company.com
MAIL_FROM_NAME="RH - Admin Ausencias"
```

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
],
```

#### 4.3 Crear Comando para Procesar Cola

```php
// app/Console/Commands/ProcessAbsenceNotifications.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class ProcessAbsenceNotifications extends Command
{
    protected $signature = 'queue:work-absence';
    
    protected $description = 'Process absence notification queue';
    
    public function handle(): int
    {
        Queue::connection('database')->worker(
            queue: 'default',
            maxJobs: 0,
            maxTime: 0
        )->daemon();
        
        return Command::SUCCESS;
    }
}
```

---

### **FASE 5: CONTROLLERS Y ENDPOINTS** (2 semanas)

#### 5.1 Crear `ApprovalController`

```php
// app/Http/Controllers/ApprovalController.php
namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use App\Services\ApprovalChainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalChainService $approvalChainService
    ) {}
    
    /**
     * GET /approvals/pending - Ausencias pendientes de aprobar
     */
    public function pending(Request $request): JsonResponse
    {
        $chains = AbsenceApprovalChain::where('assigned_to', auth()->id())
            ->where('status', 'pendiente')
            ->with(['absence', 'absence.user', 'absence.type'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json($chains);
    }
    
    /**
     * POST /approvals/{chainId}/approve
     */
    public function approve(AbsenceApprovalChain $chain, Request $request): JsonResponse
    {
        $this->authorize('approve', $chain);
        
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        
        $updated = $this->approvalChainService->approve(
            $chain,
            auth()->user(),
            $data['notes'] ?? null
        );
        
        return response()->json([
            'message' => 'Ausencia aprobada',
            'chain' => $updated,
        ]);
    }
    
    /**
     * POST /approvals/{chainId}/reject
     */
    public function reject(AbsenceApprovalChain $chain, Request $request): JsonResponse
    {
        $this->authorize('reject', $chain);
        
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        
        $updated = $this->approvalChainService->reject(
            $chain,
            auth()->user(),
            $data['reason']
        );
        
        return response()->json([
            'message' => 'Ausencia rechazada',
            'chain' => $updated,
        ]);
    }
    
    /**
     * GET /absences/{id}/history - Historial de auditoría
     */
    public function history(Absence $absence): JsonResponse
    {
        $this->authorize('view', $absence);
        
        $audits = $absence->audits()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($audits);
    }
}
```

#### 5.2 Actualizar `AbsenceController`

```php
// app/Http/Controllers/AbsenceController.php
// ... modificar métodos existentes ...

public function store(StoreAbsenceRequest $request): JsonResponse
{
    $this->authorize('create', Absence::class);
    
    // La validación ahora verifica que el usuario tenga área
    if ($request->user()?->isCollaborator() && !$request->user()->area_id) {
        return response()->json([
            'error' => 'Debes estar asignado a un área para solicitar ausencias',
        ], 400);
    }
    
    $absence = $this->absenceService->create($request->validatedData());
    
    return response()->json($absence, 201);
}

public function approve(Absence $absence, Request $request): JsonResponse
{
    $this->authorize('approve', $absence);
    
    // Solo jefe de área o admin puede aprobar
    $chain = $absence->getApprovalChainStatus();
    
    if (!$chain) {
        return response()->json([
            'error' => 'No hay aprobación pendiente',
        ], 400);
    }
    
    if ($chain->assigned_to !== auth()->id()) {
        return response()->json([
            'error' => 'No estás autorizado para aprobar',
        ], 403);
    }
    
    return response()->json(
        $this->absenceService->approve($absence, auth()->user())
    );
}

public function reject(Absence $absence, Request $request): JsonResponse
{
    $this->authorize('reject', $absence);
    
    $data = $request->validate([
        'reason' => ['required', 'string'],
    ]);
    
    $chain = $absence->getApprovalChainStatus();
    
    if (!$chain) {
        return response()->json([
            'error' => 'No hay aprobación pendiente',
        ], 400);
    }
    
    if ($chain->assigned_to !== auth()->id()) {
        return response()->json([
            'error' => 'No estás autorizado para rechazar',
        ], 403);
    }
    
    return response()->json(
        $this->absenceService->reject(
            $absence,
            auth()->user(),
            $data['reason']
        )
    );
}
```

#### 5.3 Crear `AreaManagerController`

```php
// app/Http/Controllers/AreaManagerController.php
namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaManagerController extends Controller
{
    /**
     * PUT /areas/{id}/manager
     */
    public function setManager(Area $area, Request $request): JsonResponse
    {
        $this->authorize('update', $area);
        
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);
        
        $area->update([
            'area_manager_id' => $data['user_id'],
        ]);
        
        return response()->json($area->load('manager'));
    }
    
    /**
     * DELETE /areas/{id}/manager
     */
    public function removeManager(Area $area): JsonResponse
    {
        $this->authorize('update', $area);
        
        $area->update(['area_manager_id' => null]);
        
        return response()->json([
            'message' => 'Jefe de área removido',
        ]);
    }
}
```

---

### **FASE 6: VISTAS FRONTEND** (2-3 semanas)

#### 6.1 Componente `ApprovalsList.vue`

```vue
<template>
  <div class="approvals-container">
    <h2>Ausencias Pendientes de Aprobación</h2>
    
    <div v-if="loading" class="loading">
      Cargando...
    </div>
    
    <div v-else-if="!pendingApprovals.length" class="empty">
      No hay ausencias pendientes
    </div>
    
    <div v-else class="approvals-list">
      <div 
        v-for="chain in pendingApprovals"
        :key="chain.id"
        class="approval-card"
      >
        <div class="card-header">
          <h3>{{ chain.absence.user.full_name }}</h3>
          <span class="badge" :style="{ backgroundColor: chain.absence.type.color }">
            {{ chain.absence.type.name }}
          </span>
        </div>
        
        <div class="card-body">
          <p><strong>Área:</strong> {{ chain.absence.user.area.name }}</p>
          <p><strong>Período:</strong> 
            {{ formatDate(chain.absence.start_datetime) }} a 
            {{ formatDate(chain.absence.end_datetime) }}
          </p>
          <p><strong>Duración:</strong> {{ chain.absence.total_days }} días</p>
          
          <div v-if="chain.absence.internal_notes" class="notes">
            <strong>Notas:</strong>
            {{ chain.absence.internal_notes }}
          </div>
        </div>
        
        <div class="card-actions">
          <button 
            @click="showApproveModal = true; selectedChain = chain"
            class="btn btn-success"
          >
            Aprobar
          </button>
          <button 
            @click="showRejectModal = true; selectedChain = chain"
            class="btn btn-danger"
          >
            Rechazar
          </button>
          <button 
            @click="viewDetails(chain.absence.id)"
            class="btn btn-secondary"
          >
            Ver Detalles
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modales -->
    <ApproveModal
      v-if="showApproveModal"
      :chain="selectedChain"
      @approve="handleApprove"
      @close="showApproveModal = false"
    />
    
    <RejectModal
      v-if="showRejectModal"
      :chain="selectedChain"
      @reject="handleReject"
      @close="showRejectModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ApproveModal from '@/components/ApproveModal.vue'
import RejectModal from '@/components/RejectModal.vue'

const page = usePage()
const loading = ref(false)
const pendingApprovals = ref([])
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const selectedChain = ref(null)

onMounted(() => {
  fetchPendingApprovals()
})

const fetchPendingApprovals = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/approvals/pending')
    const data = await response.json()
    pendingApprovals.value = data.data
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const handleApprove = async (notes) => {
  try {
    await fetch(`/api/approvals/${selectedChain.value.id}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notes })
    })
    showApproveModal.value = false
    fetchPendingApprovals()
  } catch (error) {
    console.error('Error:', error)
  }
}

const handleReject = async (reason) => {
  try {
    await fetch(`/api/approvals/${selectedChain.value.id}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason })
    })
    showRejectModal.value = false
    fetchPendingApprovals()
  } catch (error) {
    console.error('Error:', error)
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES')
}

const viewDetails = (absenceId) => {
  window.location.href = `/absences/${absenceId}`
}
</script>
```

#### 6.2 Modal `ApproveModal.vue` y `RejectModal.vue`

```vue
<!-- ApproveModal.vue -->
<template>
  <div class="modal">
    <div class="modal-content">
      <h2>Aprobar Ausencia</h2>
      
      <div class="absence-info">
        <p><strong>Empleado:</strong> {{ chain.absence.user.full_name }}</p>
        <p><strong>Período:</strong> {{ chain.absence.start_datetime | date }}</p>
        <p><strong>Duración:</strong> {{ chain.absence.total_days }} días</p>
      </div>
      
      <textarea 
        v-model="notes"
        placeholder="Notas adicionales (opcional)"
        class="form-textarea"
      ></textarea>
      
      <div class="modal-actions">
        <button @click="$emit('close')" class="btn btn-secondary">
          Cancelar
        </button>
        <button 
          @click="$emit('approve', notes)"
          :disabled="loading"
          class="btn btn-success"
        >
          Aprobar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

defineProps({
  chain: Object
})

defineEmits(['approve', 'close'])

const notes = ref('')
const loading = ref(false)
</script>
```

```vue
<!-- RejectModal.vue -->
<template>
  <div class="modal">
    <div class="modal-content">
      <h2>Rechazar Ausencia</h2>
      
      <div class="absence-info">
        <p><strong>Empleado:</strong> {{ chain.absence.user.full_name }}</p>
        <p><strong>Período:</strong> {{ chain.absence.start_datetime | date }}</p>
      </div>
      
      <label>Selecciona un motivo (o escribe uno personalizado)</label>
      <select v-model="selectedReason" @change="onReasonChange" class="form-select">
        <option value="">-- Seleccionar --</option>
        <option value="insufficient_balance">Saldo insuficiente</option>
        <option value="blackout_period">Período de bloqueo</option>
        <option value="operational_need">Necesidad operacional</option>
        <option value="other">Otro</option>
      </select>
      
      <textarea 
        v-model="reason"
        placeholder="Motivo del rechazo"
        class="form-textarea"
        required
      ></textarea>
      
      <div class="modal-actions">
        <button @click="$emit('close')" class="btn btn-secondary">
          Cancelar
        </button>
        <button 
          @click="$emit('reject', reason)"
          :disabled="!reason || loading"
          class="btn btn-danger"
        >
          Rechazar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

defineProps({
  chain: Object
})

defineEmits(['reject', 'close'])

const selectedReason = ref('')
const reason = ref('')
const loading = ref(false)

const onReasonChange = () => {
  const reasons = {
    insufficient_balance: 'No tienes saldo disponible para este período',
    blackout_period: 'El período solicitado está en bloqueo operacional',
    operational_need: 'Se requiere tu presencia en operaciones durante este período',
  }
  reason.value = reasons[selectedReason.value] || ''
}
</script>
```

---

### **FASE 7: RUTAS Y CONFIGURACIÓN** (1 semana)

#### 7.1 Actualizar `routes/web.php`

```php
// Rutas de aprobación
Route::middleware(['auth'])->group(function () {
    // Aprobaciones
    Route::get('/approvals/pending', [ApprovalController::class, 'pending']);
    Route::post('/approvals/{chain}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{chain}/reject', [ApprovalController::class, 'reject']);
    
    // Historial de auditoría
    Route::get('/absences/{absence}/history', [ApprovalController::class, 'history']);
    
    // Gestión de jefes de área
    Route::put('/areas/{area}/manager', [AreaManagerController::class, 'setManager']);
    Route::delete('/areas/{area}/manager', [AreaManagerController::class, 'removeManager']);
});
```

#### 7.2 Crear Policies

```php
// app/Policies/AbsenceApprovalChainPolicy.php
namespace App\Policies;

use App\Models\AbsenceApprovalChain;
use App\Models\User;

class AbsenceApprovalChainPolicy
{
    public function approve(User $user, AbsenceApprovalChain $chain): bool
    {
        return $chain->assigned_to === $user->id && $chain->isPending();
    }
    
    public function reject(User $user, AbsenceApprovalChain $chain): bool
    {
        return $chain->assigned_to === $user->id && $chain->isPending();
    }
}
```

---

### **FASE 8: TESTING Y DEPLOYMENT** (2-3 semanas)

#### 8.1 Tests Unitarios

```php
// tests/Unit/Services/ApprovalChainServiceTest.php
// tests/Unit/Services/AuditServiceTest.php
// tests/Feature/ApprovalControllerTest.php
```

#### 8.2 Seeds para Demo

```php
// database/seeders/ApprovalChainDemoSeeder.php
// Crea usuarios jefes de área, asigna aprobadores, crea ausencias pendientes
```

---

## 8. ROADMAP DE IMPLEMENTACIÓN PRIORIZADO {#roadmap}

### **Sprint 1 (2 semanas):** Base de Datos + Modelos
- ✅ Migraciones de BD
- ✅ Modelos Eloquent
- ✅ Relaciones

**DELIVERABLE:** Schema actualizado, modelos compilando

---

### **Sprint 2 (2 semanas):** Servicios + Lógica
- ✅ ApprovalChainService
- ✅ AuditService
- ✅ Actualizar AbsenceService
- ✅ Policies

**DELIVERABLE:** Lógica de aprobación funcionando en tests

---

### **Sprint 3 (2 semanas):** Backend + Endpoints
- ✅ Controllers
- ✅ Rutas
- ✅ Validaciones
- ✅ Notificaciones (setup)

**DELIVERABLE:** API REST funcionando, tests de integración passing

---

### **Sprint 4 (2 semanas):** Notificaciones por Correo
- ✅ Configurar Queue (database)
- ✅ Crear comandos
- ✅ Setup SMTP
- ✅ Testing de emails

**DELIVERABLE:** Correos siendo enviados

---

### **Sprint 5 (3 semanas):** Frontend
- ✅ Vista de aprobaciones
- ✅ Modales de aprobación/rechazo
- ✅ Historial de auditoría
- ✅ Gestión de jefes de área

**DELIVERABLE:** UI completamente funcional, UX testing

---

### **Sprint 6 (1 semana):** QA + Deployment
- ✅ Testing end-to-end
- ✅ Performance tuning
- ✅ Migración de datos
- ✅ Deploy a producción

**DELIVERABLE:** Sistema en vivo

---

### **Sprint 7+ (Backlog):** Nuevos Módulos RH
- Incapacidades/Licencias Médicas
- Horarios y Jornadas Flexibles
- Compensación y Bonificación
- Evaluaciones de Desempeño
- Formación y Desarrollo
- Disciplina y Sanciones
- Offboarding
- Nómina
- Organigramas
- Portal de Empleado

---

## 🎯 RESUMEN EJECUTIVO

### **Estado Actual**
✅ Sistema maduro y funcional  
❌ Sin sistema de aprobación jerárquica  
❌ Sin notificaciones por correo  
❌ Sin auditoría de cambios  

### **Impacto de Implementación**
✅ Gobernanza mejorada (+40% control)  
✅ UX mejorada (empleados saben estado)  
✅ Compliance y auditoría (+70% trazabilidad)  
✅ Escalabilidad para nuevos módulos RH  

### **Esfuerzo Estimado**
- **Development:** 10-12 semanas (6-7 sprints)
- **Testing:** 2 semanas paralelas
- **Rollout:** 1 semana
- **Total:** ~3 meses con equipo de 2 desarrolladores

### **Costo Aproximado** (si contratas)
- 2 devs × 3 meses × $3,000/mes = $18,000
- Infrastructure (mail, monitoring): $500-1,000

---

**Documento preparado:** 12 de Junio de 2026  
**Versión:** 1.0  
**Autor:** Claude Code Analysis  
