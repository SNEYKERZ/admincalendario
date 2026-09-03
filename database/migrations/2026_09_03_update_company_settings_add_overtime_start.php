<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->time('overtime_start')->default('18:00:00')->after('workday_end')->comment('Hora a partir de la cual se pueden registrar horas extra (Colombian labor law compliance)');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('overtime_start');
        });
    }
};
