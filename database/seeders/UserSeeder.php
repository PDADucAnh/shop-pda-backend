<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        DB::table('users')->insert([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'phone' => '0999999999',
            'username' => 'admin',
            'password' => Hash::make('123456'),
            'roles' => 'admin',
            'created_at' => now(),
        ]);

        // Khách hàng mẫu
        DB::table('users')->insert([
            'name' => 'Phạm Đức Anh',
            'email' => 'khachhang@gmail.com',
            'phone' => '0988888888',
            'username' => 'ducanh',
            'password' => Hash::make('123456'),
            'roles' => 'customer',
            'created_at' => now(),
        ]);
    }
}
