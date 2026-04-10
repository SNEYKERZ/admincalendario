<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'color',
        'is_system',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function canDelete(): bool
    {
        // Los roles del sistema no se pueden eliminar
        if ($this->is_system) {
            return false;
        }

        // No eliminar si hay usuarios asociados
        if ($this->users()->count() > 0) {
            return false;
        }

        return true;
    }

    public function getIsAdminAttribute(): bool
    {
        return in_array($this->name, ['superadmin', 'admin']);
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->name === 'superadmin';
    }
}
