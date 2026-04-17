<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar el índice único en 'email' que existía
            $table->dropUnique('users_email_unique');

            // Agregar índice único compuesto tenant_id + email
            $table->unique(['tenant_id', 'email'], 'users_tenant_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_tenant_email_unique');
            $table->unique('email', 'users_email_unique');
        });
    }
};
