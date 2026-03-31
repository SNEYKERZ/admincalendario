<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Mensual, 6 meses, 1 año
            $table->integer('duration_days'); // 30, 180, 365
            $table->decimal('price_cop', 12, 2)->default(0); // Precio en pesos
            $table->decimal('price_usd', 10, 2)->default(0); // Precio en dólares
            $table->boolean('is_active')->default(true); // Disponible para compra
            $table->integer('display_order')->default(0); // Orden de visualización
            $table->text('description')->nullable(); // Descripción del plan
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->dateTime('starts_at'); // Fecha de inicio
            $table->dateTime('expires_at'); // Fecha de expiración
            $table->boolean('is_active')->default(true); // Está activa
            $table->string('payment_method')->nullable(); // Método de pago (futuro)
            $table->string('payment_status')->nullable(); // Estado del pago (futuro)
            $table->timestamps();
        });

        Schema::create('subscription_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('conversion_rate', 8, 4)->default(1); // Rate COP to USD
            $table->decimal('cop_reference_amount', 12, 2)->default(20000); // Reference: 20,000 COP
            $table->decimal('usd_reference_amount', 10, 2)->default(5); // Reference: 5 USD
            $table->integer('show_ads_days_before')->default(5); // Días antes de mostrar anuncios
            $table->boolean('enable_payments')->default(false); // Habilitar pagos
            $table->string('payment_gateway')->nullable(); // Pasarela de pago (futuro)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_settings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
