<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('absence_type_id')
                ->constrained('absence_types')
                ->cascadeOnDelete();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->decimal('total_days', 5, 2)->default(0);
            $table->decimal('total_hours', 5, 2)->default(0);

            $table->enum('status', [
                'pendiente',
                'aprobado',
                'rechazado'
            ])->default('pendiente');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('start_datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};