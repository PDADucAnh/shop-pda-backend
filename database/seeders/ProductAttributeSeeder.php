<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = DB::table('products')->get();
        $sizeId = DB::table('attributes')->where('name', 'Size')->value('id');
        $colorId = DB::table('attributes')->where('name', 'Color')->value('id');

        foreach ($products as $p) {
            // Thêm Size M
            DB::table('product_attributes')->insert(['product_id' => $p->id, 'attribute_id' => $sizeId, 'value' => 'M']);
            // Thêm Màu Đen
            DB::table('product_attributes')->insert(['product_id' => $p->id, 'attribute_id' => $colorId, 'value' => 'Black']);
        }
    }
}
