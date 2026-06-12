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
        Schema::table('absences', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->text('internal_notes')->nullable()->after('rejection_reason');
            $table->index(['user_id', 'status', 'start_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absences', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'start_datetime']);
            $table->dropColumn(['rejection_reason', 'internal_notes']);
        });
    }
};
