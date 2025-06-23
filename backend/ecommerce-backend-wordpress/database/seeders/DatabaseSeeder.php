<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Crear usuario administrador por defecto
        User::create([
            'name' => 'Fabrizzio Admin',
            'email' => 'admin@ecommerce.test',
            'username' => 'admin',
            'first_name' => 'Fabrizzio',
            'last_name' => 'Coniglio',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Crear usuario de prueba
        User::create([
            'name' => 'Test User',
            'email' => 'test@ecommerce.test',
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Crear categorías por defecto (se sincronizarán con la API externa)
        $categories = [
            ['name' => 'Clothes', 'external_id' => 1],
            ['name' => 'Electronics', 'external_id' => 2],
            ['name' => 'Furniture', 'external_id' => 3],
            ['name' => 'Shoes', 'external_id' => 4],
            ['name' => 'Miscellaneous', 'external_id' => 5],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
