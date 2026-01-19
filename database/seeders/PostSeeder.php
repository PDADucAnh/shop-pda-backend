<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topic = DB::table('topics')->first();
        DB::table('posts')->insert([
            'topic_id' => $topic->id,
            'title' => 'Xu hướng thời trang Thu Đông 2025',
            'slug' => Str::slug('Xu hướng thời trang Thu Đông 2025'),
            'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b',
            'content' => 'Nội dung bài viết...',
            'description' => 'Tóm tắt bài viết...',
            'post_type' => 'post',
            'created_at' => now(),
        ]);
    }
}
