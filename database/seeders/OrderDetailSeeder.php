<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $order = DB::table('orders')->first();
        $product = DB::table('products')->first();

        DB::table('order_details')->insert([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => $product->price_buy,
            'qty' => 1,
            'amount' => $product->price_buy,
            'discount' => 0,
        ]);
    }
}
