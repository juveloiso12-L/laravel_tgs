<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Kasir Admin',
            'email' => 'admin@toko.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@toko.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Kasir 2',
            'email' => 'kasir2@toko.com',
            'password' => bcrypt('password'),
        ]);
    }
}