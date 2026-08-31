# 📚 DOCUMENTACIÓN DE API

## Base URL

```
http://localhost:8000/api
```

Todas las rutas requieren autenticación (`middleware: auth`)

---

## 🔐 Autenticación

### GET /me

Obtiene información del usuario autenticado.

**Respuesta:**
```json
{
  "id": 1,
  "name": "Carlos Rodríguez",
  "email": "superadmin@ausentra.com",
  "role": "superadmin",
  "is_admin": true
}
```

**Permisos:** Todos (autenticados)

---

## 📋 AUSENCIAS (Absences)

### GET /absences

Lista ausencias con filtros opcionales.

**Query Parameters:**
- `start` (string, fecha) - Fecha inicio del rango
- `end` (string, fecha) - Fecha fin del rango
- `user_ids` (string, comma-separated) - Filtrar por usuarios
- `user_id` (number) - Filtrar por usuario específico
- `status` (string) - Filtrar por estado: `pendiente`, `aprobado`, `rechazado`

**Respuesta:**
```json
[
  {
    "id": 1,
    "user_id": 2,
    "absence_type_id": 1,
    "start_datetime": "2026-09-01T00:00:00",
    "end_datetime": "2026-09-05T00:00:00",
    "status": "pendiente",
    "total_days": 5,
    "total_hours": 40,
    "notes": "Vacaciones",
    "rejection_reason": null,
    "rejected_at": null,
    "user": {...},
    "type": {...}
  }
]
```

**Permisos:** Admin y superadmin ven todas; colaboradores ven las propias

---

### POST /absences

Crear nueva ausencia.

**Body:**
```json
{
  "user_id": 2,
  "absence_type_id": 1,
  "start_datetime": "2026-09-01",
  "end_datetime": "2026-09-05",
  "include_saturday": false,
  "include_sunday": false,
  "include_holidays": true,
  "holiday_country": "CO",
  "notes": "Vacaciones"
}
```

**Permisos:** Todos (colaborador solo la suya)

---

### POST /absences/{id}/approve

Aprobar ausencia pendiente.

**Permisos:** Solo admin/superadmin

---

### POST /absences/{id}/reject

Rechazar ausencia pendiente.

**Permisos:** Solo admin/superadmin

---

## 👥 USUARIOS (Users)

### GET /admin/users

Lista todos los usuarios.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Carlos Rodríguez",
    "email": "superadmin@ausentra.com",
    "role": "superadmin",
    "identification": "80000100",
    "phone": "+57 310 100 0001",
    "is_active": true,
    "photo_url": null,
    "area_id": null,
    "area_name": null
  }
]
```

**Permisos:** Solo admin/superadmin (middleware: `can:admin`)

---

### GET /admin/users/{id}

Obtener detalles completos de un usuario.

**Response:**
```json
{
  "id": 2,
  "name": "María Elena González",
  "email": "admin@ausentra.com",
  "first_name": "María Elena",
  "last_name": "González",
  "identification": "80000101",
  "phone": "+57 310 100 0002",
  "role": "admin",
  "is_active": true,
  "birth_date": "1985-08-22",
  "hire_date": "2021-03-15",
  "photo_path": null,
  "photo_url": null,
  "allocated": 45,
  "used": 12,
  "available": 33,
  "area_id": 1,
  "area_name": "Recursos Humanos"
}
```

**Permisos:** Admin/superadmin

---

### PUT /admin/users/{id}

Actualizar usuario.

**Body:**
```json
{
  "first_name": "María",
  "last_name": "González",
  "email": "maria@example.com",
  "phone": "+57 310 200 0001",
  "role": "admin",
  "is_active": true,
  "birth_date": "1985-08-22",
  "hire_date": "2021-03-15",
  "area_id": 1
}
```

**Permisos:** Admin/superadmin

---

## 🗂️ ÁREAS (Areas)

### GET /api/areas

Lista completa de áreas con información detallada.

**Response:**
```json
{
  "areas": [
    {
      "id": 1,
      "name": "Recursos Humanos",
      "description": "Equipo de RH",
      "color": "#FF6B6B",
      "display_order": 1,
      "is_active": true,
      "employee_count": 2,
      "created_at": "2026-08-01T10:00:00"
    }
  ]
}
```

**Permisos:** Todos

---

### GET /areas-list

Lista simple de áreas para dropdowns.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Recursos Humanos",
    "color": "#FF6B6B"
  }
]
```

**Permisos:** Todos

---

## 📊 REPORTES (Reports)

### GET /reports/filters-data

Obtiene usuarios y áreas para los filtros de reportes.

**Response:**
```json
{
  "users": [
    {"id": 2, "name": "María Elena González"},
    {"id": 3, "name": "José Luis Martínez"}
  ],
  "areas": [
    {"id": 1, "name": "Recursos Humanos"},
    {"id": 2, "name": "Tecnología"}
  ]
}
```

**Cache:** Sí (en memoria del cliente)

**Permisos:** Todos

---

### GET /reports

Generar reporte de ausencias.

**Query Parameters:**
- `type` (string) - `absences` (principal)
- `start` (string, YYYY-MM-DD) - Fecha inicio
- `end` (string, YYYY-MM-DD) - Fecha fin
- `user_id` (number, optional) - Filtrar por usuario
- `area_id` (number, optional) - Filtrar por área

**Response:**
```json
{
  "type": "absences",
  "period": {
    "start": "2026-01-01",
    "end": "2026-12-31"
  },
  "filters": {
    "user_id": 2
  },
  "total": 5,
  "data": [
    {
      "id": 1,
      "empleado": "María Elena González",
      "area": "Recursos Humanos",
      "tipo": "Vacaciones",
      "inicio": "2026-01-15",
      "fin": "2026-01-19",
      "dias": 5,
      "horas": 40,
      "estado": "aprobado",
      "aprobado_por": "Carlos Rodríguez",
      "aprobado_en": "2026-01-10T09:00:00"
    }
  ]
}
```

**Permisos:**
- Superadmin: ve todos
- Admin: ve todos
- Colaborador: ve solo los suyos

---

## 🏖️ VACACIONES (Vacations)

### POST /gestion-usuarios/{user}/adjust

Ajustar días de vacaciones de un usuario.

**Body:**
```json
{
  "days": 5
}
```

**Permisos:** Admin/superadmin

---

## 🌍 FERIADOS (Holidays)

### GET /holidays

Obtiene feriados para un país y año.

**Query Parameters:**
- `year` (number, required)
- `country` (string, required) - Código ISO: CO, US, BR, etc.

**Response:**
```json
[
  {
    "date": "2026-01-01",
    "title": "Año Nuevo"
  },
  {
    "date": "2026-01-11",
    "title": "Epifanía"
  }
]
```

---

### GET /holidays/countries

Obtiene lista de países soportados.

**Response:**
```json
{
  "CO": "Colombia",
  "US": "Estados Unidos",
  "BR": "Brasil",
  "MX": "México"
}
```

---

## 📄 TIPOS DE AUSENCIA (Absence Types)

### GET /absence-types

Lista tipos de ausencia disponibles.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Vacaciones",
    "counts_as_hours": false,
    "deducts_vacation": true,
    "default_include_saturday": false,
    "default_include_sunday": false,
    "default_include_holidays": true
  }
]
```

---

## ⚠️ Códigos de Error

| Código | Descripción |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 400 | Validación fallida |
| 401 | No autenticado |
| 403 | No autorizado |
| 404 | No encontrado |
| 422 | Datos inválidos |
| 500 | Error del servidor |

---

## 🔄 Patrón de Respuesta Estándar

**Éxito:**
```json
{
  "data": {...},
  "message": "Operación exitosa"
}
```

**Error:**
```json
{
  "message": "Error message",
  "errors": {
    "field": ["error 1", "error 2"]
  }
}
```

---

## ⚡ Rate Limiting

No implementado actualmente. En producción considerar:
- 60 requests/minuto por usuario
- 1000 requests/hora por usuario

