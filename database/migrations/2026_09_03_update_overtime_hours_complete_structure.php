<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('overtime_hours');

        Schema::create('overtime_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Fecha con extracción automática de componentes
            $table->date('date');
            $table->string('day_of_week')->comment('Día de la semana (ej. Miércoles)');
            $table->integer('day_of_month')->comment('Día del mes');
            $table->string('month')->comment('Mes (ej. Agosto)');

            // Rango de tiempo
            $table->time('start_time')->comment('Hora de inicio (ej. 17:30)');
            $table->time('end_time')->comment('Hora de fin (ej. 19:00)');

            // Datos del proyecto/cliente
            $table->string('client')->nullable()->comment('Cliente o entidad');
            $table->string('project')->nullable()->comment('Proyecto o código de proyecto');
            $table->text('reason')->comment('Motivo/justificación de la labor realizada');

            // Cálculo automático de horas
            $table->decimal('hours', 5, 2)->comment('Horas calculadas automáticamente (end_time - start_time)');

            // Validaciones y estado
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Validaciones de negocio
            $table->boolean('exceeds_daily_limit')->default(false)->comment('Flag: excede 2h diarias');
            $table->boolean('exceeds_weekly_limit')->default(false)->comment('Flag: excede 12h semanales');
            $table->boolean('respects_company_schedule')->default(true)->comment('Flag: respeta jornada de la empresa');

            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'date']);
            $table->index(['tenant_id', 'user_id', 'date']);
            $table->index(['status']);
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_hours');
    }
};
