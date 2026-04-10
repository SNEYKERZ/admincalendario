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
        Schema::create('license_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique(); // Token único para la licencia
            $table->string('company_name'); // Nombre de la empresa compradora
            $table->string('company_email')->nullable(); // Email de contacto
            $table->string('company_nit')->nullable(); // NIT de la empresa
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->dateTime('starts_at'); // Fecha de inicio de la licencia
            $table->dateTime('expires_at'); // Fecha de expiración
            $table->boolean('is_active')->default(true); // Licencia activa
            $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])->default('active');
            $table->string('payment_method')->nullable(); // Método de pago usado
            $table->decimal('amount_paid', 12, 2)->nullable(); // Monto pagado
            $table->string('currency', 3)->default('COP'); // Moneda (COP/USD)
            $table->string('transaction_id')->nullable(); // ID de transacción del pago
            $table->text('notes')->nullable(); // Notas internas
            $table->dateTime('last_consulted_at')->nullable(); // Última vez que se consultó (para stats)
            $table->integer('consultation_count')->default(0); // Cuántas veces se ha consultado
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index('status');
            $table->index('expires_at');
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_tokens');
    }
};
