<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrDocument extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'uploaded_by',
        'title',
        'document_type',
        'status',
        'start_date',
        'end_date',
        'expires_at',
        'notes',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'last_alert_sent_at',
        'last_alert_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'expires_at' => 'date',
        'last_alert_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function audits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HrDocumentAudit::class, 'hr_document_id');
    }
}
