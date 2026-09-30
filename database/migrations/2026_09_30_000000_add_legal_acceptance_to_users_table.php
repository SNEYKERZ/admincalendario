<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prueba de la autorización de tratamiento de datos (Ley 1581 de 2012, art. 9)
     * y de la aceptación de los términos al registrarse.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('legal_accepted_at')->nullable();
            $table->string('legal_version', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legal_accepted_at', 'legal_version']);
        });
    }
};
