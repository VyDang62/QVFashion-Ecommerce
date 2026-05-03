<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('banners')->insert([
            ['title' => 'THỜI TRANG NỮ', 'subtitle' => 'Trải nghiệm những sản phẩm thể thao tốt nhất với bộ sưu tập mới nhất của chúng tôi.','image_path' => 'uploads/banners/4.jpg', 'position' => 'home_slider', 'order' => 1, 'is_active' => 'true', 'created_at' => now()],
            ['title' => 'THỜI TRANG NAM', 'subtitle' => 'Khám phá những xu hướng mới nhất trong thời trang thể thao và thường ngày dành cho nam giới.','image_path' => 'uploads/banners/2.png', 'position' => 'home_slider', 'order' => 2, 'is_active' => 'true', 'created_at' => now()],
            ['title' => 'TRANG SỨC', 'subtitle' => 'Nâng tầm phong cách của bạn với bộ sưu tập đồ thể thao mới nhất của chúng tôi.','image_path' => 'uploads/banners/5.jpg', 'position' => 'home_slider', 'order' => 3, 'is_active' => 'true', 'created_at' => now()],
        ]);
    }
}
