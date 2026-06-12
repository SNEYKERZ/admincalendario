# QUICK REFERENCE - GUÍA RÁPIDA DEL PROYECTO

**Imprime este documento o guárdalo en favoritos**

---

## 📁 ESTRUCTURA CRÍTICA

```
app/
├─ Models/
│  ├─ User.php              ← Usuario (admin/jefe/empleado)
│  ├─ Area.php              ← Departamento (FALTA: area_manager_id)
│  ├─ Absence.php           ← Ausencia (solicitud)
│  └─ [CREAR] AbsenceApprovalChain.php
│
├─ Services/
│  ├─ AbsenceService.php        ← Crear/aprobar/rechazar
│  ├─ AbsenceCalculationService ← Cálculo días/horas
│  ├─ VacationService.php       ← Gestionar vacaciones
│  └─ [CREAR] ApprovalChainService.php
│
├─ Http/Controllers/
│  ├─ AbsenceController.php         ← CRUD ausencias (BUG aquí)
│  └─ [CREAR] ApprovalController.php ← Aprobaciones
│
├─ Notifications/
│  ├─ AbsenceCreated.php
│  ├─ AbsenceApproved.php
│  └─ AbsenceRejected.php (INCOMPLETA)
│
└─ Policies/
   ├─ AbsencePolicy.php
   └─ [CREAR] AbsenceApprovalChainPolicy.php

database/
├─ migrations/
│  ├─ users           (AGREGAR: is_area_manager, managed_area_id)
│  ├─ areas           (AGREGAR: area_manager_id)
│  ├─ absences        (AGREGAR: rejection_reason, internal_notes)
│  └─ [CREAR]
│     ├─ absence_approval_chains
│     └─ absence_audits
│
resources/js/Pages/
├─ Calendar.vue
├─ Dashboard.vue
├─ [CREAR] Approvals.vue
└─ components/
   ├─ [CREATE] ApproveModal.vue
   └─ [CREATE] RejectModal.vue
```

---

## 🔴 TOP 3 BUGS CRÍTICOS

| # | Problema | Archivo | Línea | Fix Time |
|---|----------|---------|-------|----------|
| 1 | Sin jefe de área | `Area.php` | NO EXISTE | 2 sem |
| 2 | Aprobación automática | `AbsenceService.php` | 76-77 | 2 días |
| 3 | Sin auditoría | DB Schema | NO EXISTE | 1.5 sem |

---

## 🟠 TOP 4 BUGS ALTOS

| # | Problema | Archivo | Línea | Fix Time |
|---|----------|---------|-------|----------|
| 4 | Update sin validación | `AbsenceController.php` | 138-155 | 5 días |
| 5 | Código duplicado | 3 archivos | Múltiples | 3 días |
| 6 | Overlapping inconsistente | 2 archivos | Múltiples | 5 días |
| 7 | Notif sin motivo rechazo | `AbsenceService.php` | 235-237 | 3 días |

---

## 🗓️ ROADMAP VISUAL (4-5 SEMANAS)

```
SEMANA 1          SEMANA 2          SEMANA 3          SEMANA 4-5
─────────────     ─────────────     ─────────────     ──────────────
Migraciones       Modelos           Services          Controllers
  │                 │                 │                  │
  ├─ users          ├─ User            ├─ Approval       ├─ API endpoints
  ├─ areas          ├─ Area            │   ChainService  ├─ Frontend
  ├─ absences       ├─ Absence         ├─ AuditService   ├─ Tests
  ├─ chains*        └─ Chain*          └─ Update         └─ Deploy
  └─ audits*           & Audit*           AbsenceService
     * Nuevas tablas
```

**Tiempo total:** ~4-5 semanas con 2 devs
**Entregable:** Sistema de aprobación con correos

---

## 📊 DIAGRAMA DE FLUJO DESEADO

```
┌────────────────────────────────────────────────────────────┐
│ EMPLEADO SOLICITA AUSENCIA                                 │
└────────────┬─────────────────────────────────────────────┘
             │
             ↓
      ┌──────────────┐
      │ Crear CHAIN  │  ← ApprovalChainService::createApprovalChain()
      └──────┬───────┘
             │
      ┌──────┴─────┬──────────┐
      │ ¿Tiene     │          │
      │ jefe área? │ SÍ       │ NO
      └──────┬─────┴──────┐   │
             │            │   │
             ↓            ↓   ↓
      ┌───────────────┐  Admin   Error: No área asignada
      │ CADENA NIVEL1 │         Rechazar solicitud
      │ Jefe de Área  │
      └───────┬───────┘
              │
       EMAIL: ┌────────────────────────┐
       NOTIFY │ Jefe de Área recibe    │
              │ "Nueva solicitud"      │
              └────────────────────────┘
              │
              ↓
      ┌─────────────┐
      │ ¿Aprobado?  │
      └─┬─────────┬─┘
        │         │
    SÍ │         │ NO
        ↓        ↓
      APROBADO RECHAZADO
        │        │
        ↓        ↓
      EMAIL   EMAIL
      "✅"    "❌ Motivo: ..."
        │        │
        ├────┬───┘
             │
             ↓
      ┌──────────────┐
      │ AUDITORÍA    │  ← AbsenceAudit registra:
      │ REGISTRADA   │    - Quién, cuándo, qué cambió
      └──────────────┘    - IP, motivo, comentarios
```

---

## 🔑 CLAVES DE ÉXITO

### ✅ Hacer Sí o Sí

1. **Migrar BD primero** - Sin tablas de approval_chains y audits, todo falla
2. **Tests unitarios** - Mínimo 70% coverage para lógica de aprobación
3. **Flujo de correos** - Configurar SMTP + Queue antes de deploy
4. **Code review** - Otro dev valida cambios antes de merge
5. **Backup production** - `mysqldump` antes de ejecutar migraciones

### ❌ No Hacer

1. ❌ Saltarse tests
2. ❌ Cambiar BD sin migration
3. ❌ Mezclar jefe de área + otros cambios en un PR gigante
4. ❌ Deployar sin validar en staging
5. ❌ Ignorar los 3 bugs críticos

---

## 🎯 MÉTRICAS DE ÉXITO

| Métrica | Target | Cómo medir |
|---------|--------|-----------|
| **Coverage** | >= 70% | `php artisan test --coverage` |
| **Tests Pasan** | 100% | `php artisan test` |
| **Documentación** | 100% | Código comentado + guías |
| **Correos Enviados** | 100% | Verificar logs + inbox |
| **Auditoría Registra** | 100% | SELECT * FROM absence_audits |

---

## 🔌 PUNTOS DE INTEGRACIÓN

### Frontend → Backend

```javascript
// Approvals.vue llama a:
GET    /api/approvals/pending              ← ApprovalController@pending
POST   /api/approvals/{chainId}/approve    ← ApprovalController@approve
POST   /api/approvals/{chainId}/reject     ← ApprovalController@reject

// Absences.vue llama a:
GET    /api/absences/{id}/history          ← ApprovalController@history
```

### Backend → Database

```php
// ApprovalChainService usa:
AbsenceApprovalChain::create([...])
AbsenceApprovalChain::update([...])
AbsenceAudit::create([...])
VacationYear::deductDays()
VacationYear::restoreDays()
```

### Backend → Correos

```php
// AbsenceService llama a:
Notification::send($admins, new AbsenceCreated($absence))
$user->notify(new AbsenceApproved($absence))
$user->notify(new AbsenceRejected($absence, $reason))
// Queue procesa en background
```

---

## 🧪 CHECKLIST DE TESTING

### Unit Tests (Service Layer)

```php
✓ ApprovalChainService::createApprovalChain()
  ├─ Crea chain con level 1 si hay jefe área
  ├─ Crea chain con level 2 si requiere approval
  └─ Maneja caso sin jefe área

✓ ApprovalChainService::approve()
  ├─ Marca como aprobado
  ├─ Verifica siguiente nivel
  └─ Notifica si final

✓ ApprovalChainService::reject()
  ├─ Rechaza todas pendientes
  ├─ Restaura vacaciones
  └─ Notifica empleado

✓ AuditService::logAction()
  ├─ Registra cambios
  ├─ Captura IP
  └─ Guarda motivo
```

### Integration Tests (Controller → Service)

```php
✓ Empleado solicita ausencia
  ├─ Crea Absence con status PENDIENTE
  ├─ Crea ApprovalChain
  └─ Notifica jefe área

✓ Jefe área aprueba
  ├─ Actualiza chain status
  ├─ Si final: aprueba Absence
  ├─ Deduce vacaciones
  └─ Notifica empleado

✓ Jefe área rechaza
  ├─ Rechaza chain
  ├─ Marca Absence rechazada
  ├─ Restaura vacaciones
  └─ Notifica empleado con motivo
```

### End-to-End Tests (Full Flow)

```gherkin
Escenario: Empleado solicita, jefe aprueba
  Dado un empleado en área IT con jefe asignado
  Cuando solicita ausencia 10-15 Junio
  Entonces jefe de IT recibe email
  Y ven ausencia en /approvals/pending
  Cuando jefe aprueba
  Entonces empleado recibe email "✅ Aprobada"
  Y auditoría registra: quien, cuando, por qué
  Y vacaciones se deducen

Escenario: Jefe rechaza con motivo
  Dado una ausencia pendiente
  Cuando jefe rechaza con motivo "Período critico"
  Entonces empleado recibe email con motivo
  Y puede ver en historial por qué fue rechazada
  Y vacaciones NO se deducen
```

---

## 📞 CUÁNDO PEDIR AYUDA

| Situación | Qué hacer |
|-----------|----------|
| Test no compila | Leer error, Google, Slack equipo |
| Query lenta | Ver PLAN EXPLAIN, agregar índice |
| Email no llega | Verificar `.env`, `php artisan queue:work` |
| BD corrupta | Restaurar backup `mysql < backup.sql` |
| No entiendes código | Leer comentarios, revisar git blame |
| Disagreement de diseño | Leer `ANALISIS_PROYECTO.md`, discutir en meeting |

---

## 🚀 ANTES DE HACER PUSH

```bash
# 1. Tests locales
php artisan test                           # ¿Todos pasan?
php artisan test --coverage                # ¿>= 70%?

# 2. Linting
php -l app/ routes/ database/              # ¿Sin errores?

# 3. Migraciones
php artisan migrate --force                # ¿Se ejecutan OK?
php artisan migrate:rollback               # ¿Se revierten OK?

# 4. Git
git add -A
git commit -m "feat: add approval system"  # Mensaje claro
git push origin feature/approval-system    # Tu rama

# 5. GitHub
Crear Pull Request
Agregar descripción detallada
Pedir code review
Esperar aprobación
Mergear a main
```

---

## 📚 DOCUMENTOS DE REFERENCIA

| Documento | Cuándo leer | Contenido |
|-----------|-----------|----------|
| **RESUMEN_EJECUTIVO.md** | Inicio, para managers | 2 páginas, 10 min |
| **ANALISIS_PROYECTO.md** | Antes de empezar | 40 páginas, detalles técnicos |
| **BUGS_ENCONTRADOS.md** | Antes de fijar bugs | Código específico de cada bug |
| **CHECKLIST_IMPLEMENTACION.md** | Durante desarrollo | Paso a paso, tareas |
| **QUICK_REFERENCE.md** | Este documento | Cheat sheet rápido |

---

## 🎬 PRÓXIMO PASO (HOY)

1. ✅ Lee **RESUMEN_EJECUTIVO.md** (5 min)
2. ✅ Lee **QUICK_REFERENCE.md** (este, 5 min)
3. ✅ Abre **ANALISIS_PROYECTO.md** (para referencia)
4. 🗓️ Planifica Sprint 1 con tu equipo
5. 🚀 Inicia migraciones

---

## 💬 PREGUNTAS RÁPIDAS

**P: ¿Por dónde empiezo?**  
R: Migración de BD (semana 1), luego modelos (semana 2)

**P: ¿Cuántos tests necesito?**  
R: Mínimo 70% coverage. Enfócate en: approval chain, cálculo, notificaciones

**P: ¿Cuándo deploy a producción?**  
R: Después de 4 semanas, con staging testing, backup y rollback plan

**P: ¿Qué si encuentro un bug nuevo?**  
R: Crea issue en GitHub, reporta en Slack, agrega a BUGS_ENCONTRADOS.md

**P: ¿Cómo versionamos los cambios?**  
R: Tag: `v2.1.0` (feature), `v2.0.1` (bugfix). Ver semantic versioning

---

**Versión:** 1.0  
**Última actualización:** 12 de Junio de 2026  
**Mantén este documento a mano** 📌

