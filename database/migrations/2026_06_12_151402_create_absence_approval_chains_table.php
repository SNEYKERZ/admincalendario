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
        Schema::create('absence_approval_chains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absence_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('approval_level')->default(1);
            $table->foreignId('assigned_to')->constrained('users')->onDelete('restrict');
            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['absence_id', 'approval_level']);
            $table->index(['assigned_to', 'status']);
            $table->index(['absence_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_approval_chains');
    }
};
