<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contacts')->insert([
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@test.com',
            'phone' => '0901234567',
            'content' => 'Tôi cần tư vấn về size áo khoác',
            'created_at' => now(),
        ]);
    }
}
