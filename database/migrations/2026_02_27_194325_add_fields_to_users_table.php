<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->enum('role', ['admin', 'colaborador'])
                ->default('colaborador')
                ->after('password');

            $table->date('birth_date')->nullable()->after('role');
            $table->date('hire_date')->nullable()->after('birth_date');

            $table->string('photo_path')->nullable()->after('hire_date');

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'role',
                'birth_date',
                'hire_date',
                'photo_path'
            ]);

        });
    }
};