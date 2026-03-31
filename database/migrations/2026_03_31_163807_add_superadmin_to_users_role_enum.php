<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the enum to include superadmin
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'colaborador') DEFAULT 'colaborador'");
    }

    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'colaborador') DEFAULT 'colaborador'");
    }
};
