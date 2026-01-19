<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            DB::table('product_stores')->insert([
                'product_id' => $p->id,
                'price_root' => $p->price_buy * 0.7, // Giá vốn = 70% giá bán
                'qty' => rand(10, 100), // Số lượng ngẫu nhiên
                'created_at' => now(),
            ]);
        }
    }
}
