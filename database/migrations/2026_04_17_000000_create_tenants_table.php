<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // subdomain o identifier
            $table->string('domain')->nullable()->unique(); // dominio personalizado
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('identification')->nullable(); // NIT/RUT
            $table->string('timezone')->default('America/Bogota');
            $table->string('locale')->default('es');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main')->default(false); // tenant principal (superadmin)
            $table->timestamps();

            $table->index('slug');
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
