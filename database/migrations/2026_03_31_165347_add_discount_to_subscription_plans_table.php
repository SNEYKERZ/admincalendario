<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('price_usd');
            $table->decimal('original_price_cop', 12, 2)->nullable()->after('discount_percentage');
            $table->decimal('original_price_usd', 10, 2)->nullable()->after('original_price_cop');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'original_price_cop', 'original_price_usd']);
        });
    }
};
