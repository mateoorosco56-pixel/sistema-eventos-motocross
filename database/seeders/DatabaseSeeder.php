<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'MX1', 'description' => 'Categoría principal de motocross.'],
            ['name' => 'MX2', 'description' => 'Categoría intermedia de motocross.'],
            ['name' => 'Junior', 'description' => 'Categoría para pilotos jóvenes.'],
            ['name' => 'Open', 'description' => 'Categoría abierta.'],
        ] as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@amc.com',
            'password' => '12345678',
            'role' => 'administrador',
        ]);

        User::factory()->create([
            'name' => 'Organizador',
            'email' => 'organizador@amc.com',
            'password' => '12345678',
            'role' => 'organizador',
        ]);

        User::factory()->create([
            'name' => 'Piloto',
            'email' => 'piloto@amc.com',
            'password' => '12345678',
            'role' => 'piloto',
        ]);

        User::factory()->create([
            'name' => 'Usuario',
            'email' => 'usuario@amc.com',
            'password' => '12345678',
            'role' => 'usuario',
        ]);
    }
}