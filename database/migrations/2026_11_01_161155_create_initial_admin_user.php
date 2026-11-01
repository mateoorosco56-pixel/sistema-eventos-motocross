<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $email = config('app.admin_bootstrap_email');
$password = config('app.admin_bootstrap_password');
$name = config('app.admin_bootstrap_name', 'Administrador AMC');
        if (!$email || !$password) {
            throw new RuntimeException(
                'Faltan las variables ADMIN_BOOTSTRAP_EMAIL o ADMIN_BOOTSTRAP_PASSWORD.'
            );
        }

        if (DB::table('users')->where('email', $email)->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'administrador',
            'verification_status' => 'pending_review',
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No eliminar automáticamente la cuenta de administrador.
    }
};
