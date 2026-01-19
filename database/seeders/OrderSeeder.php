<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = DB::table('users')->where('roles', 'customer')->first();

        DB::table('orders')->insert([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => '123 Đường Láng, Hà Nội',
            'note' => 'Giao hàng giờ hành chính',
            'created_at' => now(),
        ]);
    }
}
