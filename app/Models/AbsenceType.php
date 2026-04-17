<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenceType extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'tenant_id',
        'name',
        'deducts_vacation',
        'requires_approval',
        'counts_as_hours',
        'default_include_saturday',
        'default_include_sunday',
        'default_include_holidays',
        'color',
    ];

    protected $casts = [
        'deducts_vacation' => 'boolean',
        'requires_approval' => 'boolean',
        'counts_as_hours' => 'boolean',
        'default_include_saturday' => 'boolean',
        'default_include_sunday' => 'boolean',
        'default_include_holidays' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }
}
