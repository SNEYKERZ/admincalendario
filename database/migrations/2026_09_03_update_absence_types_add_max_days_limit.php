<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absence_types', function (Blueprint $table) {
            $table->integer('max_days_limit')->nullable()->after('color')->comment('Colombian law limit: 126 for maternity (Ley 1822), 14 for paternity (Ley 2114)');
        });
    }

    public function down(): void
    {
        Schema::table('absence_types', function (Blueprint $table) {
            $table->dropColumn('max_days_limit');
        });
    }
};
