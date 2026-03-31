<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversion_rate',
        'cop_reference_amount',
        'usd_reference_amount',
        'show_ads_days_before',
        'enable_payments',
        'payment_gateway',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:4',
        'cop_reference_amount' => 'decimal:2',
        'usd_reference_amount' => 'decimal:2',
        'show_ads_days_before' => 'integer',
        'enable_payments' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public static function getSettings(): self
    {
        return self::first() ?? self::createDefault();
    }

    public static function createDefault(): self
    {
        return self::create([
            'conversion_rate' => 1,
            'cop_reference_amount' => 20000,
            'usd_reference_amount' => 5,
            'show_ads_days_before' => 5,
            'enable_payments' => false,
        ]);
    }

    public function calculateUsdFromCop(float $copAmount): float
    {
        if ($this->cop_reference_amount == 0) {
            return 0;
        }

        return ($copAmount / $this->cop_reference_amount) * $this->usd_reference_amount;
    }

    public function calculateCopFromUsd(float $usdAmount): float
    {
        if ($this->usd_reference_amount == 0) {
            return 0;
        }

        return ($usdAmount / $this->usd_reference_amount) * $this->cop_reference_amount;
    }

    public function recalculateConversionRate(): void
    {
        if ($this->cop_reference_amount > 0 && $this->usd_reference_amount > 0) {
            $this->conversion_rate = $this->usd_reference_amount / $this->cop_reference_amount;
            $this->save();
        }
    }
}
