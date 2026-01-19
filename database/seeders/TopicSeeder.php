<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('topics')->insert([
            ['name' => 'Tin Tức', 'slug' => 'tin-tuc', 'created_at' => now()],
            ['name' => 'Kiến Thức Thời Trang', 'slug' => 'kien-thuc', 'created_at' => now()],
        ]);
    }
}
