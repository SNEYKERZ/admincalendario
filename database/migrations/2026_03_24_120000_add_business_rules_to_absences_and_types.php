<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absence_types', function (Blueprint $table) {
            $table->boolean('default_include_saturday')->default(true)->after('counts_as_hours');
            $table->boolean('default_include_sunday')->default(true)->after('default_include_saturday');
            $table->boolean('default_include_holidays')->default(true)->after('default_include_sunday');
        });

        Schema::table('absences', function (Blueprint $table) {
            $table->boolean('include_saturday')->default(true)->after('end_datetime');
            $table->boolean('include_sunday')->default(true)->after('include_saturday');
            $table->boolean('include_holidays')->default(true)->after('include_sunday');
            $table->string('holiday_country', 2)->default(config('business_calendar.default_country', 'CO'))->after('include_holidays');
        });

        DB::table('absence_types')
            ->where('name', 'Vacaciones')
            ->update([
                'default_include_saturday' => false,
                'default_include_sunday' => false,
                'default_include_holidays' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('absences', function (Blueprint $table) {
            $table->dropColumn([
                'include_saturday',
                'include_sunday',
                'include_holidays',
                'holiday_country',
            ]);
        });

        Schema::table('absence_types', function (Blueprint $table) {
            $table->dropColumn([
                'default_include_saturday',
                'default_include_sunday',
                'default_include_holidays',
            ]);
        });
    }
};
