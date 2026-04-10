<?php

namespace Database\Seeders;

use App\Models\SubscriptionSettings;
use Illuminate\Database\Seeder;

class SubscriptionSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! SubscriptionSettings::exists()) {
            SubscriptionSettings::create([
                'conversion_rate' => 0.00025,
                'cop_reference_amount' => 20000,
                'usd_reference_amount' => 5,
                'show_ads_days_before' => 5,
                'enable_payments' => false,
            ]);

            $this->command->info('✅ Settings de suscripción sembrados');
        }
    }
}
