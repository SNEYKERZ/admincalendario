<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite (tests): rebuild the column via the schema builder.
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['superadmin', 'admin', 'colaborador'])->default('colaborador')->change();
            });

            return;
        }

        // Modify the enum to include superadmin
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'colaborador') DEFAULT 'colaborador'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'colaborador'])->default('colaborador')->change();
            });

            return;
        }

        // Revert to original enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'colaborador') DEFAULT 'colaborador'");
    }
};
