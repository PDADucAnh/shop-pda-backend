<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            ['name' => 'Trang chủ', 'link' => '/', 'type' => 'custom', 'position' => 'mainmenu'],
            ['name' => 'Sản phẩm', 'link' => '/products', 'type' => 'page', 'position' => 'mainmenu'],
            ['name' => 'Bài viết', 'link' => '/posts', 'type' => 'page', 'position' => 'mainmenu'],
            ['name' => 'Liên hệ', 'link' => '/contact', 'type' => 'page', 'position' => 'mainmenu'],
            ['name' => 'Chính sách', 'link' => '/policy', 'type' => 'page', 'position' => 'footermenu'],
        ];

        foreach ($menus as $m) {
            DB::table('menus')->insert(array_merge($m, ['created_at' => now()]));
        }
    }
}
