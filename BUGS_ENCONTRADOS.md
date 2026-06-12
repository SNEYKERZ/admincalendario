# BUGS Y PROBLEMAS ENCONTRADOS - CÓDIGO ESPECÍFICO

**Fecha:** 12 de Junio de 2026  
**Severidad:** 🔴 Crítica, 🟠 Alta, 🟡 Media, 🟢 Baja  

---

## BUG #1: APROBACIÓN AUTOMÁTICA PARA ADMINS 🔴 CRÍTICA

**Ubicación:** `app/Services/AbsenceService.php:76-77`

```php
// ❌ CÓDIGO ACTUAL (PROBLEMA)
$isApproved = auth()->user()->isAdmin();
$status = $isApproved ? AbsenceStatus::APPROVED : AbsenceStatus::PENDING;

// Resultado: Si un admin crea, se aprueba inmediatamente sin registro
```

**Problema:**
- Admin que crea ausencia no deja rastro de aprobación
- No se puede auditar quién aprobó
- Incumplimiento de gobernanza
- El `approved_by` y `approved_at` se llenan automáticamente, sin intervención

**Línea exacta:**
```php
'approved_by' => $isApproved ? auth()->id() : null,
'approved_at' => $isApproved ? now() : null,
```

**Impacto:**
- Posible manipulación de datos
- No hay cadena de aprobación real
- Auditoría incompleta

**Solución:**
```php
// ✅ CÓDIGO CORRECTO
$status = AbsenceStatus::PENDING; // SIEMPRE pendiente al inicio

// Crear cadena de aprobación automática
$this->approvalChainService->createApprovalChain($absence);

// El jefe de área (no el admin que crea) aprueba después
```

---

## BUG #2: VALIDACIÓN DE UPDATE DEBILITADA 🔴 CRÍTICA

**Ubicación:** `app/Http/Controllers/AbsenceController.php:138-155`

```php
// ❌ CÓDIGO ACTUAL (PROBLEMA)
public function update(Request $request, Absence $absence): JsonResponse
{
    $this->authorize('update', $absence);

    $data = $request->validate([
        'start_datetime' => ['required', 'date'],
        'end_datetime' => ['required', 'date', 'after:start_datetime'],
        'include_saturday' => ['sometimes', 'boolean'],
        'include_sunday' => ['sometimes', 'boolean'],
        'include_holidays' => ['sometimes', 'boolean'],
        'holiday_country' => ['sometimes', 'string', 'size:2'],
        'reason' => ['nullable', 'string'],
    ]);

    return response()->json(
        $this->absenceService->update($absence, $data)
    );
}

// ❌ PROBLEMAS:
// 1. NO valida overlapping con otras ausencias
// 2. NO valida saldo de vacaciones
// 3. NO valida límites de tipos especiales (Cumpleaños)
// 4. NO valida que ausencia esté en estado PENDIENTE
// 5. NO usa StoreAbsenceRequest (DRY violation)
```

**Escenario de Explotación:**

```
1. Empleado solicita ausencia: 10-20 Junio (10 días)
2. Ausencia está PENDIENTE
3. Empleado edita a: 10-25 Junio (15 días)
4. Sistema NO valida si tiene 15 días disponibles
5. Ausencia se actualiza sin validación
6. Si se aprueba después, queda con 15 días deducidos aunque no debería
```

**Solución:**
```php
public function update(Request $request, Absence $absence): JsonResponse
{
    $this->authorize('update', $absence);

    // NO permitir editar si no está en PENDIENTE
    if ($absence->status !== AbsenceStatus::PENDING->value) {
        return response()->json([
            'error' => 'Solo puedes editar ausencias pendientes'
        ], 422);
    }

    // Usar FormRequest con validación completa
    $validated = (new StoreAbsenceRequest())
        ->validateResolved();

    return response()->json(
        $this->absenceService->update($absence, $validated)
    );
}
```

---

## BUG #3: DUPLICACIÓN DE LÓGICA DE CÁLCULO 🟠 ALTA

**Ubicaciones:**
1. `app/Http/Requests/StoreAbsenceRequest.php:150-159`
2. `app/Services/AbsenceService.php:45-50`
3. `app/Services/AbsenceService.php:163-173`

```php
// ❌ CÓDIGO DUPLICADO #1 - StoreAbsenceRequest
$calculationService = app(AbsenceCalculationService::class);
$calculation = $calculationService->calculate(
    $type,
    $start,
    $end,
    $calculationService->resolveOptions($type, $this->all())
);

// ❌ CÓDIGO DUPLICADO #2 - AbsenceService::create()
$calculation = $this->absenceCalculationService->calculate(
    $type,
    $start,
    $end,
    $this->absenceCalculationService->resolveOptions($type, $data)
);

// ❌ CÓDIGO DUPLICADO #3 - AbsenceService::update()
$calculation = $this->absenceCalculationService->calculate(
    $type,
    $start,
    $end,
    $this->absenceCalculationService->resolveOptions($type, [...])
);
```

**Problema:**
- Si se cambia lógica de cálculo, hay 3 lugares que cambiar
- Alto riesgo de inconsistencia
- Mantenimiento difícil

**Escenario de Error:**
```
1. Dev cambia lógica en StoreAbsenceRequest
2. Olvida cambiar en AbsenceService::create()
3. Validación pasa en request
4. Pero servicio calcula diferente
5. Datos inconsistentes en BD
```

**Solución:**
```php
// Crear método en ServiceRequest para centralizar
public function validateAndCalculate(): array
{
    $this->validate();
    return [
        'validated' => $this->validated(),
        'calculation' => $this->cachedCalculation,
    ];
}
```

---

## BUG #4: OVERLAPPING LOGIC INCONSISTENTE 🟠 ALTA

**Ubicación 1:** `app/Http/Requests/StoreAbsenceRequest.php:134-144`
```php
// ❌ VERSIÓN 1 (REQUEST)
$overlap = Absence::where('user_id', $user->id)
    ->whereIn('status', ['pendiente', 'aprobado'])
    ->where(function ($q) use ($start, $end) {
        $q->whereBetween('start_datetime', [$start, $end])
            ->orWhereBetween('end_datetime', [$start, $end])
            ->orWhere(function ($q2) use ($start, $end) {
                $q2->where('start_datetime', '<=', $start)
                   ->where('end_datetime', '>=', $end);
            });
    })
    ->exists();
```

**Ubicación 2:** `app/Services/AbsenceService.php:58-64`
```php
// ❌ VERSIÓN 2 (SERVICE - DIFERENTE!)
$overlap = Absence::where('user_id', $user->id)
    ->whereIn('status', [AbsenceStatus::PENDING->value, AbsenceStatus::APPROVED->value])
    ->where(function ($q) use ($start, $end) {
        $q->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start);
    })
    ->exists();
```

**Diferencias:**
- Version 1: 3 condiciones con `whereBetween` y `orWhere`
- Version 2: 2 condiciones simples con `<` y `>`

**Escenario problemático:**
```
Ausencia existente: 10-15 Junio
Nueva solicitud: 12-18 Junio

Version 1 (correcta):
├─ 12-18 está entre 10-15? NO
├─ Fin 18 está entre 10-15? NO
├─ Inicio 12 <= 10 AND Fin 18 >= 15? SÍ → RECHAZA

Version 2 (simplista):
├─ 10 < 18? SÍ
├─ 15 > 12? SÍ → RECHAZA

En este caso es OK, pero hay casos edge donde fallan diferente
```

**Solución:**
```php
// Crear helper method en Absence model
public static function hasOverlap(User $user, $start, $end, ?int $excludeId = null): bool
{
    return static::where('user_id', $user->id)
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->whereIn('status', [
            AbsenceStatus::PENDING->value,
            AbsenceStatus::APPROVED->value
        ])
        ->where('start_datetime', '<', $end)
        ->where('end_datetime', '>', $start)
        ->exists();
}

// Usar en todos lados:
if (Absence::hasOverlap($user, $start, $end)) {
    // Rechazar
}
```

---

## BUG #5: FALTA DE VALIDACIÓN EN ESTADO DE AUSENCIA 🟠 ALTA

**Ubicación:** `app/Http/Controllers/AbsenceController.php:138-155`

```php
// ❌ PROBLEMA: No valida estado
public function update(Request $request, Absence $absence): JsonResponse
{
    // Permite editar ausencia APROBADA, PENDIENTE o RECHAZADA
    // Sin validar si está en estado correcto
}

// Escenario:
// 1. Ausencia está APROBADA (status = 'aprobado')
// 2. Empleado edita a fechas diferentes
// 3. Sistema actualiza sin validar vacaciones deducidas
// 4. Quedan días inconsistentes
```

**Solución:**
```php
// En Policy o Controller
if (!in_array($absence->status, [
    AbsenceStatus::PENDING->value
])) {
    return response()->json([
        'error' => 'Solo puedes editar ausencias pendientes'
    ], 422);
}
```

---

## BUG #6: NOTIFICACIÓN INCOMPLETA EN RECHAZO 🟠 ALTA

**Ubicación:** `app/Services/AbsenceService.php:235-237`

```php
// ❌ CÓDIGO ACTUAL
public function reject(Absence $absence, User $admin): Absence
{
    return $this->setStatus($absence, AbsenceStatus::REJECTED, $admin);
}

// ❌ PROBLEMAS:
// 1. NO recibe "reason" como parámetro
// 2. NO guarda razón de rechazo en BD (campo falta)
// 3. Notificación AbsenceRejected no tiene información del motivo
// 4. Empleado no sabe por qué fue rechazado
```

**Comprobación en BD:**
```sql
-- Este campo NO existe en tabla absences
SELECT * FROM absences LIMIT 1;
-- Se debería ver: rejection_reason NULL
```

**Impacto:**
- Empleado confundido: "¿por qué me rechazaron?"
- Sin trazabilidad
- Mala experiencia de usuario

**Solución:**
```php
// En migration: agregar campo
$table->text('rejection_reason')->nullable();

// En controller
$data = $request->validate(['reason' => 'required']);

// En servicio
$absence->update([
    'status' => AbsenceStatus::REJECTED->value,
    'rejection_reason' => $reason,
]);

$absence->user->notify(new AbsenceRejected($absence, $reason));
```

---

## BUG #7: SIN CONCEPTO DE JEFE DE ÁREA 🔴 CRÍTICA

**Ubicación:** Modelo `Area` y `User`

```php
// ❌ MODELO ACTUAL
class Area extends Model {
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'area_id');
    }
    // ❌ NO EXISTE: area_manager_id
    // ❌ NO EXISTE: relación con manager (User)
}

// ❌ CONSECUENCIAS
// 1. No se puede asignar jefe de área
// 2. Notificaciones van a TODOS los admins
// 3. No hay restricción: admin de IT aprueba ausencia de HR
// 4. No hay jerarquía de aprobación
```

**Impacto en flujo:**
```
HOY:
Empleado solicita ausencia
    ↓
TODOS los admins reciben email
    ↓
Cualquier admin puede aprobar/rechazar
    ↓
Sin distinción de áreas
    ↓
Caos

DESEADO:
Empleado solicita ausencia
    ↓
Jefe de su área recibe email
    ↓
Solo jefe puede aprobar/rechazar (o escalado a admin)
    ↓
Control jerárquico real
```

**Solución:** Ver `ANALISIS_PROYECTO.md` sección 7 (Fase 1)

---

## BUG #8: FALTA DE AUDITORÍA 🔴 CRÍTICA

**Ubicación:** No existe tabla `absence_audits`

```php
// ❌ PROBLEMA
// Preguntas sin respuesta:
// - ¿Quién aprobó la ausencia?
// - ¿Cuándo exactamente?
// - ¿Con qué comentario?
// - ¿Desde qué IP?
// - ¿Cambios previos si fue editada?

// SÍ EXISTE: approved_by, approved_at
// PERO: No hay historial de cambios de estado

// Escenario:
// 1. Ausencia creadapor empleado (PENDIENTE)
// 2. Admin rechaza (RECHAZADO)
// 3. Admin aprueba (APROBADO)
// → En BD solo se ve el estado final: APROBADO
// → No hay registro de que fue rechazada antes
```

**Solución:** Ver `ANALISIS_PROYECTO.md` sección 7 Fase 1

---

## BUG #9: VALIDACIONES ASINCRÓNICAS FALTANTES 🟡 MEDIA

**Ubicación:** `resources/js/Pages/` (Componentes Vue)

```vue
<!-- ❌ PROBLEMA EN ABSENCEMODAL -->
<template>
  <div>
    <input v-model="startDate" type="date" />
    <input v-model="endDate" type="date" />
    <!-- NO hay validación en tiempo real -->
    <!-- NO hay feedback de overlapping -->
    <!-- NO hay cálculo de días mientras escribo -->
  </div>
</template>

<!-- ❌ UX Problema:
Empleado escribe fechas
    ↓
Hace click en "Solicitar"
    ↓
Backend RECHAZA (overlapping)
    ↓
Empleado tiene que reintentar
    ↓
Mala experiencia
-->

<!-- ✅ MEJOR UX:
Empleado escribe fecha inicio
    ↓
Sistema valida + calcula días EN TIEMPO REAL
    ↓
Muestra: "✓ Disponibles 15 días"
    ↓
Escribe fecha fin
    ↓
Sistema advierte: "⚠️ Conflicto con ausencia 20-25 Junio"
    ↓
Empleado puede corregir antes de enviar
-->
```

**Solución:**
```vue
<script setup>
import { debounce } from '@/lib/utils'

const validateOverlap = debounce(async () => {
    const res = await fetch(
        `/api/absences/validate-overlap?user_id=${userId}&start=${startDate}&end=${endDate}`
    )
    const { overlap } = await res.json()
    hasOverlap.value = !!overlap
}, 300)

watch([startDate, endDate], validateOverlap)
</script>
```

---

## BUG #10: AUSENCIAS DE EDICIÓN EN ESTADO APROBADO 🟡 MEDIA

**Ubicación:** `app/Policies/AbsencePolicy.php`

```php
// ❌ PROBLEMA
public function update(User $user, Absence $absence): bool
{
    return $user->id === $absence->user_id;
}

// Esto permite:
// - Empleado edita ausencia PENDIENTE ✓ OK
// - Empleado edita ausencia APROBADA ✗ NO DEBERÍA
// - Admin edita ausencia de otro admin ✗ NO DEBERÍA

// Escenario:
// 1. Ausencia aprobada y deducida del saldo
// 2. Empleado la edita a 20 días en lugar de 10
// 3. Saldo se recalcula mal
```

**Solución:**
```php
public function update(User $user, Absence $absence): bool
{
    // Solo editar si está PENDIENTE
    if ($absence->status !== AbsenceStatus::PENDING->value) {
        return false;
    }
    
    return $user->id === $absence->user_id;
}
```

---

## BUG #11: TRANSACCIÓN FALTANTE EN UPDATE 🟡 MEDIA

**Ubicación:** `app/Services/AbsenceService.php:153-228`

```php
// ❌ PROBLEMA
public function update(Absence $absence, array $data): Absence
{
    // NO está en DB::transaction()
    // Si falla a mitad, queda inconsistente
    
    $absence->update([...]);
    
    if ($delta > 0) {
        $this->vacationService->deductDays(...); // ¿Qué si falla?
    }
}

// Escenario:
// 1. Actualizar ausencia: 10 → 15 días
// 2. Delta = 5
// 3. System intenta deducir 5 días
// 4. Deducción falla (error BD)
// 5. Ausencia quedó actualizada pero vacaciones no
// → Inconsistencia
```

**Solución:**
```php
public function update(Absence $absence, array $data): Absence
{
    return DB::transaction(function () use ($absence, $data) {
        // Toda la lógica aquí
        // Si falla cualquier paso, se revierte todo
    });
}
```

---

## BUG #12: FALTA ÍNDICES EN BD 🟡 MEDIA

**Ubicación:** Migraciones

```sql
-- ❌ ÍNDICES FALTANTES

-- absence_approval_chains
-- Está bien: unique (absence_id, approval_level), index (assigned_to, status)

-- absences
-- FALTA: index (user_id, status, start_datetime)
-- Causa: Query lenta cuando filtros de empleado+estado

-- users
-- FALTA: index (area_id, is_active)
-- Causa: Query lenta para listar empleados por área

-- absence_audits
-- FALTA: index (user_id, created_at)
-- Causa: Query lenta para auditoría por usuario
```

**Solución:**
```php
// En migración
Schema::table('absences', function (Blueprint $table) {
    $table->index(['user_id', 'status', 'start_datetime']);
});

Schema::table('users', function (Blueprint $table) {
    $table->index(['area_id', 'is_active']);
});

Schema::table('absence_audits', function (Blueprint $table) {
    $table->index(['user_id', 'created_at']);
});
```

---

## BUG #13: LOGGING DEFICIENTE 🟡 MEDIA

**Ubicación:** `app/Services/AbsenceService.php:114-126`

```php
// ❌ SOLO LOG DE ERROR
catch (QueryException $exception) {
    if ($this->isMissingNotificationsTableException($exception)) {
        Log::warning('Tabla notifications no existe...');
        return;
    }
}

// FALTA:
// - Log cuando aprobación exitosa
// - Log cuando rechazo
// - Log de cambios de estado
// - Debugging de quién aprobó qué
```

**Solución:**
```php
Log::info('Absence approved', [
    'absence_id' => $absence->id,
    'approved_by' => auth()->id(),
    'approved_at' => now(),
    'user_id' => $absence->user_id,
]);
```

---

## RESUMEN DE SEVERIDAD

| Severidad | Cantidad | Ejemplos |
|-----------|----------|----------|
| 🔴 Crítica | 3 | Aprobación automática, Sin jefe área, Sin auditoría |
| 🟠 Alta | 4 | Validación update, Duplicación código, Overlapping inconsistente, Notif incompleta |
| 🟡 Media | 5 | Validaciones asincrónicas, Edición aprobada, Transacción, Índices, Logging |
| 🟢 Baja | 0 | - |

---

## SCORING DE IMPACTO

### Por Riesgo Regulatorio/Compliance
1. 🔴 SIN AUDITORÍA (90 puntos) - Incumplimiento legal
2. 🔴 SIN JEFE DE ÁREA (85 puntos) - Falta de gobernanza
3. 🔴 APROBACIÓN AUTOMÁTICA (80 puntos) - Trazabilidad nula

### Por Riesgo de Data Corruption
1. 🟠 VALIDACIÓN UPDATE DÉBIL (75 puntos)
2. 🟠 OVERLAPPING INCONSISTENTE (70 puntos)
3. 🟡 FALTA TRANSACCIÓN UPDATE (65 puntos)

### Por Impacto en UX
1. 🟡 SIN VALIDACIONES ASINCRÓNICAS (60 puntos)
2. 🟠 NOTIF INCOMPLETA (55 puntos)
3. 🟢 LOGGING DEFICIENTE (40 puntos)

---

**Total de bugs encontrados:** 13  
**Críticos:** 3  
**Altos:** 4  
**Medios:** 5  
**Bajos:** 1  

**Recomendación:** Fijar todos los 🔴 CRÍTICOS antes de agregar nuevas features.

---

**Documento preparado:** 12 de Junio de 2026  
**Versión:** 1.0  
