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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_area_manager')->default(false)->after('role');
            $table->foreignId('managed_area_id')
                ->nullable()
                ->after('is_area_manager')
                ->constrained('areas')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['managed_area_id']);
            $table->dropColumn(['is_area_manager', 'managed_area_id']);
        });
    }
};
