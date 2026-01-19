<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$banners = [
            ['name' => 'New Collection 2025', 'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b', 'position' => 'slideshow'],
            ['name' => 'Sale Off 50%', 'image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8', 'position' => 'slideshow'],
            ['name' => 'Quảng cáo phụ kiện', 'image' => 'https://images.unsplash.com/photo-1515347619252-60a6bf4fffce', 'position' => 'ads'],
        ];

        foreach ($banners as $b) {
            DB::table('banners')->insert([
                'name' => $b['name'],
                'image' => $b['image'],
                'link' => '/products',
                'position' => $b['position'],
                'created_at' => now(),
            ]);
            }
    }
}
