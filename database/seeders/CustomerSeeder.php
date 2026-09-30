<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Umum',
                'phone' => '0',
                'email' => null,
                'address' => 'Walk-in Customer',
                'member_type' => 'umum',
                'points' => 0,
            ],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'budi@example.com',
                'address' => 'Jl. Merdeka No. 10, Jakarta',
                'member_type' => 'member',
                'points' => 1500,
            ],
            [
                'name' => 'Siti Rahayu',
                'phone' => '081298765432',
                'email' => 'siti@example.com',
                'address' => 'Jl. Sudirman No. 25, Bandung',
                'member_type' => 'vip',
                'points' => 5000,
            ],
            [
                'name' => 'Ahmad Wijaya',
                'phone' => '081345678901',
                'email' => 'ahmad@example.com',
                'address' => 'Jl. Gatot Subroto No. 5, Surabaya',
                'member_type' => 'member',
                'points' => 2300,
            ],
            [
                'name' => 'Dewi Lestari',
                'phone' => '081356789012',
                'email' => 'dewi@example.com',
                'address' => 'Jl. Thamrin No. 15, Medan',
                'member_type' => 'vip',
                'points' => 8000,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}