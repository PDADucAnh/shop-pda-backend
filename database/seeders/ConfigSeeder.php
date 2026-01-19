<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('configs')->insert([
            'site_name' => 'PDA Fashion',
            'email' => 'contact@pdafashion.com',
            'phone' => '0909123456',
            'hotline' => '1900 8888',
            'address' => 'Số 1 Võ Văn Ngân, TP. Thủ Đức, TP.HCM',
            'status' => 1
        ]);
    }
}
