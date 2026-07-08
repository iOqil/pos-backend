<?php

namespace Database\Seeders;

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
        // Admin user
        $admin = \App\Models\User::create([
            'name' => 'Admin Kassir',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Categories
        $catBooks = \App\Models\Category::create(['name' => 'Kitoblar', 'slug' => 'kitoblar']);
        $catStationery = \App\Models\Category::create(['name' => 'Kanstovar', 'slug' => 'kanstovar']);

        // Brands
        $brandX = \App\Models\Brand::create(['name' => 'X-Brand']);

        // Products (Static ones first)
        $products = [
            [
                'name' => 'O\'tkan Kunlar',
                'slug' => 'otkan-kunlar',
                'category_id' => $catBooks->id,
                'brand_id' => $brandX->id,
                'description' => 'Abdulla Qodiriy romani',
                'price' => 35000,
                'cost_price' => 20000,
                'stock_quantity' => 10,
                'barcode' => '2000000000018',
                'sku' => 'BK-001',
                'has_variants' => false
            ],
            [
                'name' => 'Ruchka (Ko\'k)',
                'slug' => 'ruchka-kok',
                'category_id' => $catStationery->id,
                'brand_id' => $brandX->id,
                'description' => 'Yozuv quroli',
                'price' => 3000,
                'cost_price' => 1500,
                'stock_quantity' => 50,
                'barcode' => '2000000000025',
                'sku' => 'ST-001',
                'has_variants' => false
            ],
            [
                'name' => 'Daftar 12 varaq',
                'slug' => 'daftar-12-varaq',
                'category_id' => $catStationery->id,
                'brand_id' => $brandX->id,
                'description' => 'Yozuv daftari',
                'price' => 1500,
                'cost_price' => 800,
                'stock_quantity' => 100,
                'barcode' => '2000000000032',
                'sku' => 'ST-002',
                'has_variants' => false
            ]
        ];

        foreach ($products as $p) {
            \App\Models\Product::create($p);
        }

        // Generate 50 Fake Products
        for ($i = 1; $i <= 50; $i++) {
            $isBook = rand(0, 1) === 1;
            \App\Models\Product::create([
                'name' => ($isBook ? 'Kitob ' : 'Kanstovar ') . 'Model ' . $i,
                'slug' => 'product-model-' . $i,
                'category_id' => $isBook ? $catBooks->id : $catStationery->id,
                'brand_id' => $brandX->id,
                'description' => 'Ajoyib mahsulot tavsifi bu yerda bo\'ladi...',
                'price' => rand(5, 50) * 1000, // 5,000 to 50,000
                'cost_price' => rand(2, 4) * 1000,
                'stock_quantity' => rand(0, 100),
                'barcode' => '200000' . str_pad($i + 1000, 7, '0', STR_PAD_LEFT),
                'sku' => 'ITM-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'has_variants' => false
            ]);
        }
    }
}
