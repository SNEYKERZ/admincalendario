<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_request_id',
        'approver_id',
        'status',
        'comment',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function employeeRequest(): BelongsTo
    {
        return $this->belongsTo(EmployeeRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rechazado');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }
}
