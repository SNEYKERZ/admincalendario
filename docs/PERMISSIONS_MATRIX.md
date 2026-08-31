# 🔐 MATRIZ DE PERMISOS

## Resumen Rápido

| Recurso | Superadmin | Admin | Colaborador |
|---------|------------|-------|-------------|
| **Users** | CRUD | R | R (self) |
| **Areas** | CRUD | R (all) | R |
| **Absences** | R,A,B,R | R,A,B,D,E | CRUD (own) |
| **Reports** | R (all) | R (all) | R (self) |
| **Vacations** | R,E | R | R (self) |
| **Documents** | CRUD | R,E | R,S |

**Operaciones:**
- C: Create, R: Read, U: Update, D: Delete
- A: Approve, B: Reject, E: Edit, S: Sign

---

## Detalle por Recurso

### 👥 USUARIOS (Users)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Ver lista | ✅ Todos | ✅ Solo colaboradores | ❌ |
| Ver detalle | ✅ Cualquiera | ✅ Cualquiera | ✅ Sí mismo |
| Crear | ✅ | ✅ | ❌ |
| Editar | ✅ | ✅ | ❌ |
| Eliminar | ✅ | ❌ | ❌ |
| Importar masivo | ✅ | ✅ | ❌ |
| Ajustar vacaciones | ✅ | ✅ | ❌ |

**Ubicación de Lógica:**
- Policy: `app/Policies/UserPolicy.php`
- Controlador: `app/Http/Controllers/Admin/UserController.php`
- Middleware: `can:admin` para CRUD

---

### 🗂️ ÁREAS (Areas)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Ver lista | ✅ Todas | ✅ Todas | ✅ Todas |
| Ver detalle | ✅ | ✅ | ✅ |
| Crear | ✅ | ❌ | ❌ |
| Editar | ✅ | ❌ | ❌ |
| Eliminar | ✅ | ❌ | ❌ |
| Asignar manager | ✅ | ❌ | ❌ |

**Ubicación de Lógica:**
- Controlador: `app/Http/Controllers/AreaController.php`
- Endpoints:
  - GET `/api/areas` - Lista completa
  - GET `/areas-list` - Para dropdowns
  - GET `/api/areas/{id}` - Detalle

---

### 📋 AUSENCIAS (Absences)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Ver todas | ✅ | ✅ | ❌ |
| Ver propias | ✅ | ✅ | ✅ |
| Crear | ✅ | ✅ | ✅ |
| Editar propia (pendiente) | ✅ | ❌ | ✅ |
| Editar ajena | ❌ | ❌ | ❌ |
| Aprobar | ✅ | ✅ | ❌ |
| Rechazar | ✅ | ✅ | ❌ |
| Eliminar propia | ✅ | ❌ | ✅ (si pendiente) |

**Ubicación de Lógica:**
- Policy: `app/Policies/AbsencePolicy.php`
- Controlador: `app/Http/Controllers/AbsenceController.php`
- Regla: Solo se edita si es propietario Y status = PENDING

---

### 📊 REPORTES (Reports)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Generar por persona | ✅ (todos) | ✅ (todos) | ✅ (sí mismo) |
| Generar por área | ✅ (todas) | ✅ (todas) | ❌ |
| Exportar CSV | ✅ | ✅ | ✅ (limitado) |

**Ubicación de Lógica:**
- Controlador: `app/Http/Controllers/ReportController.php`
- Endpoints:
  - GET `/reports` - Generar reporte
  - GET `/reports/export` - Exportar CSV
  - GET `/reports/filters-data` - Usuarios y áreas para filtros

---

### 🏖️ VACACIONES (Vacations)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Ver asignadas | ✅ Todos | ✅ Todos | ✅ Propio |
| Ver disponibles | ✅ Todos | ✅ Todos | ✅ Propio |
| Ajustar días | ✅ | ✅ | ❌ |

**Ubicación de Lógica:**
- Controlador: `app/Http/Controllers/VacationController.php`
- Endpoint: `POST /gestion-usuarios/{user}/adjust`

---

### 📄 DOCUMENTOS (Documents)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Ver todos | ✅ | ✅ | ❌ |
| Ver propios | ✅ | ✅ | ✅ |
| Crear | ✅ | ✅ | ✅ |
| Editar | ✅ | ✅ | ✅ (propios) |
| Eliminar | ✅ | ✅ (propios) | ✅ (propios) |
| Solicitar firma | ✅ | ✅ | ✅ |
| Firmar | ✅ | ✅ | ✅ |

**Ubicación de Lógica:**
- Controlador: `app/Http/Controllers/HrDocumentController.php`

---

### ⚙️ CONFIGURACIÓN DEL SISTEMA (System Management)

| Acción | Superadmin | Admin | Colaborador |
|--------|------------|-------|-------------|
| Ver settings | ✅ | ❌ | ❌ |
| Editar settings | ✅ | ❌ | ❌ |
| Gestionar planes | ✅ | ❌ | ❌ |
| Gestionar licencias | ✅ | ❌ | ❌ |
| Ver anuncios | ✅ | ❌ | ❌ |

**Ubicación de Lógica:**
- Middleware: `can:superadmin` en todas las rutas
- Controlador: `app/Http/Controllers/SystemManagementController.php`

---

## Implementación

### Backend (Laravel Policies)

```php
// Ejemplo: AbsencePolicy.php
public function update(User $user, Absence $absence): bool
{
    // Solo el dueño puede editar si está pendiente
    return $user->id === $absence->user_id 
        && $absence->status === AbsenceStatus::PENDING;
}

public function approve(User $user, Absence $absence): bool
{
    // Solo admins pueden aprobar
    return $user->isAdmin();
}
```

### Frontend (Vue Computed)

```typescript
// Reportes.vue
const canEditAbsence = computed(() => {
    return currentUserId.value === absence.value.user_id 
        && absence.value.status === 'pendiente';
});
```

---

## Notas Importantes

1. **Superadmin vs Admin:**
   - Superadmin: Acceso a gestión del sistema
   - Admin: Acceso solo a datos de usuarios/áreas

2. **Ausencias Rechazadas:**
   - Visibles 3 días después del rechazo
   - Después se ocultan del calendario

3. **Reportes:**
   - Admin ve TODOS los reportes (no filtrado)
   - Colaborador ve solo el suyo

4. **Validación:**
   - Backend: Policies y Middleware
   - Frontend: UI readinessde campos según rol

---

## Auditoría

- ✅ Users: Correcto
- ✅ Areas: Corregido (ahora devuelve todas)
- ✅ Absences: Corregido (admin no puede editar)
- ✅ Reports: Corregido (endpoint unificado)
- ✅ Vacations: Correcto
- ✅ Documents: Correcto
- ✅ System: Correcto (solo superadmin)

