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
        Schema::create('absence_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absence_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('action');
            $table->json('changes')->nullable();
            $table->text('reason')->nullable();
            $table->ipAddress()->nullable();
            $table->timestamps();

            $table->index(['absence_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_audits');
    }
};
