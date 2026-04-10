<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->timestamp('last_alert_sent_at')->nullable()->after('file_size');
            $table->string('last_alert_type', 20)->nullable()->after('last_alert_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->dropColumn(['last_alert_sent_at', 'last_alert_type']);
        });
    }
};
