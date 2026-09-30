<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan & Minuman', 'slug' => 'makanan-minuman', 'description' => 'Produk makanan dan minuman kemasan'],
            ['name' => 'Sembako', 'slug' => 'sembako', 'description' => 'Kebutuhan pokok sehari-hari'],
            ['name' => 'Perawatan Diri', 'slug' => 'perawatan-diri', 'description' => 'Produk perawatan pribadi dan kebersihan'],
            ['name' => 'Perawatan Rumah', 'slug' => 'perawatan-rumah', 'description' => 'Produk pembersih dan perawatan rumah'],
            ['name' => 'Snack & Camilan', 'slug' => 'snack-camilan', 'description' => 'Makanan ringan dan camilan'],
            ['name' => 'Minuman', 'slug' => 'minuman', 'description' => 'Minuman dalam kemasan'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}