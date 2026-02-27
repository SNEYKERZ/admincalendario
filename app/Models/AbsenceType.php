<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbsenceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'deducts_vacation',
        'requires_approval',
        'counts_as_hours',
        'color',
    ];

    protected $casts = [
        'deducts_vacation' => 'boolean',
        'requires_approval' => 'boolean',
        'counts_as_hours' => 'boolean',
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