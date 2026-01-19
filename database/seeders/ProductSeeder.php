<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$catNu = DB::table('categories')->where('slug', 'thoi-trang-nu')->value('id');
        $catNam = DB::table('categories')->where('slug', 'thoi-trang-nam')->value('id');

        $products = [
            ['name' => 'Đầm Dạ Hội Cao Cấp', 'cat' => $catNu, 'price' => 1500000, 'img' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8'],
            ['name' => 'Áo Sơ Mi Nữ Lụa', 'cat' => $catNu, 'price' => 550000, 'img' => 'https://images.unsplash.com/photo-1598532163257-ae3c6b2524b6'],
            ['name' => 'Áo Vest Nam Lịch Lãm', 'cat' => $catNam, 'price' => 2000000, 'img' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35'],
            ['name' => 'Quần Jean Nam Classic', 'cat' => $catNam, 'price' => 450000, 'img' => 'https://images.unsplash.com/photo-1542272454315-4c01d7abdf4a'],
        ];

        foreach ($products as $p) {
            DB::table('products')->insert([
                'category_id' => $p['cat'],
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'thumbnail' => $p['img'],
                'content' => 'Nội dung chi tiết sản phẩm...',
                'description' => 'Mô tả ngắn sản phẩm...',
                'price_buy' => $p['price'],
                'created_at' => now(),
            ]);
        }
    }
}
