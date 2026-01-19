<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            DB::table('product_images')->insert([
                'product_id' => $p->id,
                'image' => $p->thumbnail, // Dùng lại ảnh đại diện làm ảnh chi tiết
                'alt' => $p->name,
            ]);
        }
    }
}
