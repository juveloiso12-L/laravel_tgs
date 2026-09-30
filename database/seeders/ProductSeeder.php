<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('slug');

        $products = [
            // Makanan & Minuman
            [
                'category_id' => $categories['makanan-minuman']->id,
                'code' => 'BRG001',
                'barcode' => '899123456001',
                'name' => 'Indomie Goreng',
                'description' => 'Mie instan rasa ayam bawang',
                'price' => 3500,
                'cost_price' => 2800,
                'stock' => 100,
                'min_stock' => 10,
                'unit' => 'pcs',
            ],
            [
                'category_id' => $categories['makanan-minuman']->id,
                'code' => 'BRG002',
                'barcode' => '899123456002',
                'name' => 'Aqua 600ml',
                'description' => 'Air mineral kemasan 600ml',
                'price' => 4000,
                'cost_price' => 3000,
                'stock' => 50,
                'min_stock' => 10,
                'unit' => 'btl',
            ],
            [
                'category_id' => $categories['minuman']->id,
                'code' => 'BRG003',
                'barcode' => '899123456003',
                'name' => 'Teh Botol Sosro',
                'description' => 'Teh manis dalam botol 400ml',
                'price' => 5000,
                'cost_price' => 4000,
                'stock' => 50,
                'min_stock' => 10,
                'unit' => 'btl',
            ],
            [
                'category_id' => $categories['sembako']->id,
                'code' => 'BRG004',
                'barcode' => '899123456004',
                'name' => 'Beras 5 Kg',
                'description' => 'Beras premium 5kg',
                'price' => 75000,
                'cost_price' => 68000,
                'stock' => 20,
                'min_stock' => 5,
                'unit' => 'karung',
            ],
            [
                'category_id' => $categories['sembako']->id,
                'code' => 'BRG005',
                'barcode' => '899123456005',
                'name' => 'Minyak Goreng 1 Liter',
                'description' => 'Minyak goreng sawit 1L',
                'price' => 18000,
                'cost_price' => 16000,
                'stock' => 30,
                'min_stock' => 5,
                'unit' => 'btl',
            ],
            [
                'category_id' => $categories['sembako']->id,
                'code' => 'BRG006',
                'barcode' => '899123456006',
                'name' => 'Gula Pasir 1 Kg',
                'description' => 'Gula pasir putih 1kg',
                'price' => 17000,
                'cost_price' => 15000,
                'stock' => 30,
                'min_stock' => 5,
                'unit' => 'kg',
            ],
            // Snack & Camilan
            [
                'category_id' => $categories['snack-camilan']->id,
                'code' => 'BRG007',
                'barcode' => '899123456007',
                'name' => 'Chitato Sapi Panggang',
                'description' => 'Keripik kentang rasa sapi panggang',
                'price' => 9000,
                'cost_price' => 7000,
                'stock' => 40,
                'min_stock' => 5,
                'unit' => 'pcs',
            ],
            [
                'category_id' => $categories['snack-camilan']->id,
                'code' => 'BRG008',
                'barcode' => '899123456008',
                'name' => 'Oreo Original',
                'description' => 'Biskuit cokelat dengan krim vanilla',
                'price' => 8500,
                'cost_price' => 6500,
                'stock' => 40,
                'min_stock' => 5,
                'unit' => 'pcs',
            ],
            [
                'category_id' => $categories['snack-camilan']->id,
                'code' => 'BRG009',
                'barcode' => '899123456009',
                'name' => 'Tango Wafel Vanilla',
                'description' => 'Wafel rasa vanilla',
                'price' => 5000,
                'cost_price' => 3800,
                'stock' => 50,
                'min_stock' => 5,
                'unit' => 'pcs',
            ],
            // Perawatan Diri
            [
                'category_id' => $categories['perawatan-diri']->id,
                'code' => 'BRG010',
                'barcode' => '899123456010',
                'name' => 'Pepsodent Herbal 170g',
                'description' => 'Pasta gigi herbal',
                'price' => 15000,
                'cost_price' => 12000,
                'stock' => 30,
                'min_stock' => 5,
                'unit' => 'tube',
            ],
            [
                'category_id' => $categories['perawatan-diri']->id,
                'code' => 'BRG011',
                'barcode' => '899123456011',
                'name' => 'Lifebuoy Cair 200ml',
                'description' => 'Sabun cair antibakteri',
                'price' => 12000,
                'cost_price' => 9500,
                'stock' => 30,
                'min_stock' => 5,
                'unit' => 'btl',
            ],
            [
                'category_id' => $categories['perawatan-diri']->id,
                'code' => 'BRG012',
                'barcode' => '899123456012',
                'name' => 'Sunsilk Shampoo 170ml',
                'description' => 'Shampoo rambut keras',
                'price' => 22000,
                'cost_price' => 18000,
                'stock' => 25,
                'min_stock' => 5,
                'unit' => 'btl',
            ],
            // Perawatan Rumah
            [
                'category_id' => $categories['perawatan-rumah']->id,
                'code' => 'BRG013',
                'barcode' => '899123456013',
                'name' => 'Bayclin 800ml',
                'description' => 'Pewangi pakaian',
                'price' => 18000,
                'cost_price' => 14000,
                'stock' => 20,
                'min_stock' => 5,
                'unit' => 'btl',
            ],
            [
                'category_id' => $categories['perawatan-rumah']->id,
                'code' => 'BRG014',
                'barcode' => '899123456014',
                'name' => 'Sunlight Jeruk 800ml',
                'description' => 'Sabun cuci piring',
                'price' => 16000,
                'cost_price' => 12500,
                'stock' => 20,
                'min_stock' => 5,
                'unit' => 'btl',
            ],
            // Additional products
            [
                'category_id' => $categories['minuman']->id,
                'code' => 'BRG015',
                'barcode' => '899123456015',
                'name' => 'Pocari Sweat 500ml',
                'description' => 'Minuman ion',
                'price' => 7000,
                'cost_price' => 5500,
                'stock' => 40,
                'min_stock' => 5,
                'unit' => 'btl',
            ],
            [
                'category_id' => $categories['minuman']->id,
                'code' => 'BRG016',
                'barcode' => '899123456016',
                'name' => 'Kopi Kapal Api Sachet',
                'description' => 'Kopi hitam sachet',
                'price' => 2000,
                'cost_price' => 1500,
                'stock' => 100,
                'min_stock' => 20,
                'unit' => 'sachet',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}