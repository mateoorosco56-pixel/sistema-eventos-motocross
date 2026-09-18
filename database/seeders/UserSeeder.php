<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@motocross.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin12345'),
                'role' => 'administrador',
            ]
        );

        User::updateOrCreate(
            ['email' => 'organizador@motocross.com'],
            [
                'name' => 'Organizador',
                'password' => Hash::make('Organizador12345'),
                'role' => 'organizador',
            ]
        );

        User::updateOrCreate(
            ['email' => 'piloto@motocross.com'],
            [
                'name' => 'Piloto',
                'password' => Hash::make('Piloto12345'),
                'role' => 'piloto',
            ]
        );

        User::updateOrCreate(
            ['email' => 'usuario@motocross.com'],
            [
                'name' => 'Usuario',
                'password' => Hash::make('Usuario12345'),
                'role' => 'usuario',
            ]
        );
    }
}