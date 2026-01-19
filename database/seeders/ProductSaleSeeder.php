<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = DB::table('products')->first();
        if ($product) {
            DB::table('product_sales')->insert([
                'product_id' => $product->id,
                'name' => 'Flash Sale',
                'price_sale' => $product->price_buy * 0.8, // Giảm 20%
                'date_begin' => Carbon::now(),
                'date_end' => Carbon::now()->addDays(7),
                'created_at' => now(),
            ]);
        }
    }
}
