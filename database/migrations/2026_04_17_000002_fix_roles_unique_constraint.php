<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Eliminar el índice único en 'name' que existía
            $table->dropUnique('roles_name_unique');

            // Agregar tenant_id si no existe
            if (! Schema::hasColumn('roles', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            }

            // Agregar índice único compuesto tenant_id + name
            $table->unique(['tenant_id', 'name'], 'roles_tenant_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_tenant_name_unique');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id']);
            $table->unique('name', 'roles_name_unique');
        });
    }
};
