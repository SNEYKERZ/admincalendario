<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * PasswordController y ResetUserPassword guardaban la contraseña en texto
 * plano (User no tenía el cast "hashed"). Esta migración hashea las que no
 * tengan formato de hash. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'password'])
            ->whereNotNull('password')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    if ($user->password === '' || Hash::isHashed($user->password)) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['password' => Hash::make($user->password)]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible por diseño.
    }
};
