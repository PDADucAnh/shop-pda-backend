<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cats = ['Thời trang Nam', 'Thời trang Nữ', 'Phụ kiện', 'Giày dép'];
        foreach ($cats as $c) {
            DB::table('categories')->insert([
                'name' => $c,
                'slug' => Str::slug($c),
                'description' => 'Mô tả cho ' . $c,
                'created_at' => now(),
            ]);

        }
    }
}
