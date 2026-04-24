<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrDocument extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'uploaded_by',
        'title',
        'document_type',
        'document_mode',
        'status',
        'template_id',
        'area_id',
        'signature_status',
        'signature_method',
        'signature_data',
        'signature_name',
        'signature_requested_at',
        'signed_at',
        'signed_by',
        'rejected_at',
        'rejection_reason',
        'is_personal_upload',
        'start_date',
        'end_date',
        'expires_at',
        'notes',
        'template_content',
        'rendered_content',
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
        'signature_requested_at' => 'datetime',
        'signed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_personal_upload' => 'boolean',
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

    public function template(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_id');
    }

    public function derivedDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'template_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
