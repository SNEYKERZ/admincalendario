<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admin_audits', function (Blueprint $table) {
            $table->id();

            // SuperAdmin real que realizó la acción
            $table->foreignId('superadmin_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Usuario SuperAdmin que realizó la acción');

            // Usuario impersonado (null si no está impersonando)
            $table->foreignId('impersonated_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuario siendo impersonado (null si en contexto global)');

            // Tenant asociado (null si contexto global)
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->onDelete('set null')
                ->comment('Tenant de la acción (null si en contexto global)');

            // Tipo de acción
            $table->enum('action', [
                'login',
                'logout',
                'start_impersonation',
                'end_impersonation',
                'activate_tenant',
                'deactivate_tenant',
                'suspend_tenant',
                'create_user',
                'update_user',
                'delete_user',
                'view_tenant',
                'other',
            ])->comment('Tipo de acción realizada');

            // Descripción de la acción
            $table->string('description')
                ->nullable()
                ->comment('Descripción legible de la acción');

            // Cambios realizados (JSON)
            $table->json('changes')
                ->nullable()
                ->comment('Cambios realizados (before/after)');

            // Información de la solicitud
            $table->string('ip_address')
                ->nullable()
                ->comment('Dirección IP de la solicitud');

            $table->string('user_agent')
                ->nullable()
                ->comment('User-Agent del navegador');

            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['superadmin_id', 'created_at'], 'idx_superadmin_audit_time');
            $table->index(['impersonated_user_id', 'created_at'], 'idx_impersonated_time');
            $table->index(['tenant_id', 'created_at'], 'idx_tenant_audit_time');
            $table->index(['action', 'created_at'], 'idx_action_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_audits');
    }
};
