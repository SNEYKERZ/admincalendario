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
        \DB::statement('ALTER TABLE vacation_years DROP FOREIGN KEY vacation_years_user_id_foreign');
        \DB::statement('ALTER TABLE vacation_years DROP INDEX vacation_years_user_id_year_unique');
        \DB::statement('ALTER TABLE vacation_years ADD UNIQUE vacation_years_tenant_id_user_id_year_unique (tenant_id, user_id, year)');
        \DB::statement('ALTER TABLE vacation_years ADD CONSTRAINT vacation_years_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('ALTER TABLE vacation_years DROP FOREIGN KEY vacation_years_user_id_foreign');
        \DB::statement('ALTER TABLE vacation_years DROP INDEX vacation_years_tenant_id_user_id_year_unique');
        \DB::statement('ALTER TABLE vacation_years ADD UNIQUE vacation_years_user_id_year_unique (user_id, year)');
        \DB::statement('ALTER TABLE vacation_years ADD CONSTRAINT vacation_years_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
};
