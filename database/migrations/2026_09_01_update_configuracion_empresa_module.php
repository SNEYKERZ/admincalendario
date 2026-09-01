<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')
            ->where('slug', 'configuracion-empresa')
            ->update(['is_core' => false]);
    }

    public function down(): void
    {
        DB::table('modules')
            ->where('slug', 'configuracion-empresa')
            ->update(['is_core' => true]);
    }
};
