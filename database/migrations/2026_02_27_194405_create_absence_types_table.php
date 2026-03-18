<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_types', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->boolean('deducts_vacation')->default(false);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('counts_as_hours')->default(false);

            $table->string('color', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_types');
    }
};