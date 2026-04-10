<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LicenseToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'company_name',
        'company_email',
        'company_nit',
        'plan_id',
        'starts_at',
        'expires_at',
        'is_active',
        'status',
        'payment_method',
        'amount_paid',
        'currency',
        'transaction_id',
        'notes',
        'last_consulted_at',
        'consultation_count',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'amount_paid' => 'decimal:2',
        'last_consulted_at' => 'datetime',
        'consultation_count' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }

    public function scopeByCompany($query, string $companyName)
    {
        return $query->where('company_name', 'like', "%{$companyName}%");
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si la licencia está vigente
     */
    public function isValid(): bool
    {
        return $this->is_active
            && $this->status === 'active'
            && $this->expires_at->isFuture();
    }

    /**
     * Verifica si la licencia ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verifica si la licencia está próxima a vencer (por defecto 7 días)
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->isValid()
            && $this->expires_at->diffInDays(now()) <= $days;
    }

    /**
     * Días restantes hasta que expire la licencia
     */
    public function daysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at);
    }

    /**
     * Genera un token único
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Busca una licencia por su token
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }

    /**
     * Registra una consulta de la licencia (para estadísticas)
     */
    public function recordConsultation(): void
    {
        $this->update([
            'last_consulted_at' => now(),
            'consultation_count' => $this->consultation_count + 1,
        ]);
    }

    /**
     * Activa la licencia
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Desactiva la licencia
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'status' => 'suspended',
        ]);
    }

    /**
     * Cancela la licencia
     */
    public function cancel(): void
    {
        $this->update([
            'is_active' => false,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Renueva la licencia con nueva fecha de expiración
     */
    public function renew(int $days): self
    {
        $newExpiresAt = $this->isExpired()
            ? now()->addDays($days)
            : $this->expires_at->addDays($days);

        $this->update([
            'starts_at' => $this->isExpired() ? now() : $this->starts_at,
            'expires_at' => $newExpiresAt,
            'is_active' => true,
            'status' => 'active',
        ]);

        return $this;
    }

    /**
     * Obtiene la respuesta de verificación de licencia
     */
    public function getVerificationResponse(): array
    {
        $this->recordConsultation();

        return [
            'valid' => $this->isValid(),
            'status' => $this->status,
            'company_name' => $this->company_name,
            'plan_name' => $this->plan?->name,
            'plan_id' => $this->plan_id,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->daysRemaining(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Formatea el monto pagado
     */
    public function getFormattedAmount(): string
    {
        if ($this->currency === 'USD') {
            return '$'.number_format($this->amount_paid, 2);
        }

        return '$'.number_format($this->amount_paid, 0, ',', '.').' COP';
    }

    /**
     * Obtiene el estado label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Activa',
            'expired' => 'Expirada',
            'suspended' => 'Suspendida',
            'cancelled' => 'Cancelada',
            default => 'Desconocido',
        };
    }

    /**
     * Obtiene el color del estado para UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => $this->isExpiringSoon() ? 'warning' : 'success',
            'expired' => 'danger',
            'suspended' => 'warning',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
