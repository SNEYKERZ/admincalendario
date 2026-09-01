<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('request_type', [
                'certificado',
                'permiso_especial',
                'documento',
                'cambio_datos',
                'otro',
            ]);

            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_requests');
    }
};
