<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_days',
        'price_cop',
        'price_usd',
        'is_active',
        'display_order',
        'description',
        'discount_percentage',
        'original_price_cop',
        'original_price_usd',
    ];

    protected $casts = [
        'price_cop' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'duration_days' => 'integer',
        'discount_percentage' => 'decimal:2',
        'original_price_cop' => 'decimal:2',
        'original_price_usd' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function getFormattedPriceCop(): string
    {
        return '$'.number_format($this->price_cop, 0, ',', '.').' COP';
    }

    public function getFormattedPriceUsd(): string
    {
        return '$'.number_format($this->price_usd, 2);
    }

    public function getFormattedOriginalPriceCop(): string
    {
        return '$'.number_format($this->original_price_cop, 0, ',', '.').' COP';
    }

    public function getFormattedOriginalPriceUsd(): string
    {
        return '$'.number_format($this->original_price_usd, 2);
    }

    public function getDurationLabel(): string
    {
        return match ($this->duration_days) {
            30 => 'Mensual',
            180 => '6 Meses',
            365 => '1 Año',
            default => $this->duration_days.' días',
        };
    }

    public function hasDiscount(): bool
    {
        return $this->discount_percentage > 0;
    }

    public function calculateDiscountedPrice(float $originalPrice, float $discountPercentage): float
    {
        return $originalPrice * (1 - ($discountPercentage / 100));
    }
}
