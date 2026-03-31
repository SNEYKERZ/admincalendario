<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_logo')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_identification')->nullable();
            $table->integer('vacation_days_default')->default(15);
            $table->integer('vacation_days_advance')->default(30);
            $table->time('workday_start')->default('08:00:00');
            $table->time('workday_end')->default('17:00:00');
            $table->boolean('allow_weekend_absences')->default(false);
            $table->boolean('allow_holiday_absences')->default(false);
            $table->boolean('require_approval_for_all')->default(true);
            $table->boolean('notification_email_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
