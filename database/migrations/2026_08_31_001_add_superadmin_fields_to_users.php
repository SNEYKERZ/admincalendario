<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregar campo para identificar SuperAdmin sin tenant operativo
            $table->boolean('is_superadmin_only')
                ->default(false)
                ->after('role')
                ->comment('True si es SuperAdmin que no pertenece operativamente a ningún tenant');

            // Índices para queries frecuentes
            $table->index(['id', 'is_superadmin_only'], 'idx_user_superadmin_only');
            $table->index(['role', 'is_superadmin_only'], 'idx_role_superadmin_only');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_user_superadmin_only');
            $table->dropIndex('idx_role_superadmin_only');
            $table->dropColumn('is_superadmin_only');
        });
    }
};
