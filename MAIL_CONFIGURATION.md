# 📧 CONFIGURACIÓN DE CORREOS

## Requisitos

Para que los correos funcionen, necesitas:

1. **Configurar SMTP en `.env`**
2. **Crear tabla `jobs` para queue**
3. **Procesar la queue**

---

## 1. CONFIGURACIÓN DEL `.env`

### Opción A: Gmail (Recomendado para Testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="RH - Admin Ausencias"
QUEUE_CONNECTION=database
```

**Nota:** En Gmail debes crear una "App Password":
1. Ir a https://myaccount.google.com/security
2. Activar "2-Step Verification"
3. Crear "App Password" para "Mail" y "Windows Computer"
4. Usar ese password en `MAIL_PASSWORD`

### Opción B: SendGrid

```env
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=tu-sendgrid-key
MAIL_FROM_ADDRESS=noreply@company.com
MAIL_FROM_NAME="RH - Admin Ausencias"
QUEUE_CONNECTION=database
```

### Opción C: Mailtrap (Testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-mailtrap-username
MAIL_PASSWORD=tu-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="RH - Admin Ausencias"
QUEUE_CONNECTION=database
```

### Opción D: Local (Solo Logs)

```env
MAIL_MAILER=log
QUEUE_CONNECTION=database
```

Con esto, los correos se escriben en `storage/logs/laravel.log`

---

## 2. CONFIGURAR QUEUE EN BASE DE DATOS

```bash
# Crear tabla jobs
php artisan queue:table

# Ejecutar migrations
php artisan migrate
```

Esto crea la tabla `jobs` necesaria para procesar notificaciones en background.

---

## 3. PROCESAR LA QUEUE

### Opción A: Modo Manual (Testing)

```bash
# Procesar jobs en background
php artisan queue:work

# O con delay entre intentos
php artisan queue:work --delay=3
```

Deja esta terminal abierta. Los correos se procesarán automáticamente.

### Opción B: Con Supervisor (Producción)

Crea `/etc/supervisor/conf.d/admin-calendar-worker.conf`:

```ini
[program:admin-calendar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/admincalendar/artisan queue:work database --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/admin-calendar-worker.log
stopasgroup=true
```

Luego:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start admin-calendar-worker:*
```

### Opción C: Cron (Alternativa Ligera)

Agrega a crontab:

```cron
* * * * * cd /path/to/admincalendar && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. VERIFICAR QUE FUNCIONA

### Teste en local primero:

```bash
# 1. Configurar .env con MAIL_MAILER=log
# 2. Solicitar una ausencia como empleado
# 3. Ver logs:
tail -f storage/logs/laravel.log

# Deberías ver el email que se "envió"
```

### Luego con SMTP real:

```bash
# 1. Configurar .env con SMTP (Gmail, SendGrid, etc)
# 2. Terminal 1: Procesar queue
php artisan queue:work

# 3. Terminal 2: En otra terminal, solicitar ausencia
php artisan tinker
>>> $user = User::first();
>>> Auth::login($user);

# 4. Ir al navegador y solicitar una ausencia
# 5. En Terminal 1 verás que se procesa el job:
# "Processed: App\Notifications\AbsencePendingApproval"

# 6. Verificar bandeja de entrada del jefe de área
```

---

## 5. NOTIFICACIONES CONFIGURADAS

| Nombre | Cuándo se envía | A quién | Canal |
|--------|-----------------|--------|-------|
| **AbsencePendingApproval** | Empleado solicita | Jefe de Área | mail + database |
| **AbsenceApproved** | Jefe aprueba | Empleado | mail + database |
| **AbsenceRejected** | Jefe rechaza | Empleado | mail + database (con motivo) |

---

## 6. VARIABLES DE ENTORNO COMPLETAS

```env
# Correos
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="RH - Admin Ausencias"

# Queue (para procesar notificaciones)
QUEUE_CONNECTION=database

# Redis (opcional, si usas caché con Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 7. TROUBLESHOOTING

### "Connection refused"
- Verificar que SMTP_HOST y SMTP_PORT sean correctos
- En Gmail: verificar que creaste "App Password"
- En Mailtrap: copiar credenciales correctas

### "Queue jobs no se procesan"
- Verificar que `QUEUE_CONNECTION=database` está en `.env`
- Verificar que tabla `jobs` existe: `php artisan migrate`
- Verificar que `php artisan queue:work` está corriendo

### "Correos no llegan"
- Verificar en carpeta Spam
- En testing: ver `storage/logs/laravel.log`
- En Gmail: verificar que "Menos segura" está habilitada

### "SQLSTATE[HY000]"
- Problema de base de datos en queue
- Ejecutar: `php artisan migrate`
- Limpiar: `php artisan queue:flush`

---

## 8. TESTING

Para testing sin enviar correos reales:

```php
// En tests/Feature/ApprovalControllerTest.php
use Illuminate\Support\Facades\Mail;

Mail::fake();

// Hacer la acción
$this->actingAs($approver)->post('/approvals/{$chain}/approve');

// Verificar que se intentó enviar
Mail::assertSent(AbsenceApproved::class);
```

---

## 📝 RESUMEN RÁPIDO

```bash
# 1. Editar .env con SMTP (ver Opción A-D arriba)
nano .env

# 2. Crear tabla jobs
php artisan queue:table
php artisan migrate

# 3. Procesar queue en otra terminal
php artisan queue:work

# 4. Testing: Solicitar ausencia como empleado
# 5. Verificar: Jefe recibe email en su bandeja
```

---

**Documentado:** 12 de Junio 2026  
**Mantén este archivo para referencia**
