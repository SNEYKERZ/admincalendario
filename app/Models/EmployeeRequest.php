<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeRequest extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'assigned_to',
        'request_type',
        'status',
        'title',
        'description',
        'attachment',
        'rejection_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequestApproval::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rechazado');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('request_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }

    public function getTypeLabel(): string
    {
        return match ($this->request_type) {
            'certificado' => 'Certificado Laboral',
            'permiso_especial' => 'Permiso Especial',
            'documento' => 'Documento',
            'cambio_datos' => 'Cambio de Datos',
            'otro' => 'Otra Solicitud',
            default => 'Desconocido',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            default => 'Desconocido',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pendiente' => 'warning',
            'aprobado' => 'success',
            'rechazado' => 'danger',
            default => 'secondary',
        };
    }

    public function approve(User $approver, ?string $comment = null): RequestApproval
    {
        $this->update(['status' => 'aprobado']);

        return RequestApproval::create([
            'employee_request_id' => $this->id,
            'approver_id' => $approver->id,
            'status' => 'aprobado',
            'comment' => $comment,
            'decided_at' => now(),
        ]);
    }

    public function reject(User $approver, string $reason): RequestApproval
    {
        $this->update([
            'status' => 'rechazado',
            'rejection_reason' => $reason,
        ]);

        return RequestApproval::create([
            'employee_request_id' => $this->id,
            'approver_id' => $approver->id,
            'status' => 'rechazado',
            'comment' => $reason,
            'decided_at' => now(),
        ]);
    }
}
