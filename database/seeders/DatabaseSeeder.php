<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
                // 1. Dữ liệu nền tảng
            ConfigSeeder::class,
            UserSeeder::class,
            BannerSeeder::class,
            MenuSeeder::class,
            ContactSeeder::class,

                // 2. Sản phẩm & Thuộc tính
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,       // Cần Category
            ProductImageSeeder::class,  // Cần Product
            ProductStoreSeeder::class,  // Cần Product
            ProductAttributeSeeder::class, // Cần Product & Attribute
            ProductSaleSeeder::class,   // Cần Product

                // 3. Bài viết
            TopicSeeder::class,
            PostSeeder::class,          // Cần Topic

                // 4. Đơn hàng
            OrderSeeder::class,         // Cần User
            OrderDetailSeeder::class,   // Cần Order & Product
        ]);
    }
}
