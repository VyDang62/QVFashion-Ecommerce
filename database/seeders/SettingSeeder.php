<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ---NHÓM: CHUNG ---
            [
                'key' => 'site_name',
                'value' => 'QV Fashion',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Tên hiển thị trên tiêu đề website.',
            ],
            [
                'key' => 'contact_email',
                'value' => 'support@qvfashion.com',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Email nhận thông báo từ khách hàng.',
            ],
            [
                'key' => 'phone_number',
                'value' => '0349630014',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Số điện thoại nhận thông báo từ khách hàng.',
            ],
            [
                'key' => 'website_logo',
                'value' => '/uploads/logos/logo.png',
                'type' => 'image',
                'group' => 'general',
                'description' => 'Logo của website.',
            ],
            [
                'key' => 'address',
                'value' => 'Mỹ Hòa, Long Xuyên, An Giang',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Địa chỉ của website.',
            ],
            [
                'key' => 'description',
                'value' => 'Chúng tôi cam kết mang đến những trang phục thật đẹp.',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Mô tả của website.',
            ],
            [
                'key' => 'facebook_link',
                'value' => 'https://www.facebook.com/vydang6204/',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Đường dẫn trang facebook của website.',
            ],
            [
                'key' => 'x_link',
                'value' => 'https://www.facebook.com/vydang6204/',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Đường dẫn trang X của website.',
            ],
            [
                'key' => 'instagram_link',
                'value' => 'https://www.facebook.com/vydang6204/',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Đường dẫn trang instagram của website.',
            ],
            [
                'key' => 'youtube_link',
                'value' => 'https://www.facebook.com/vydang6204/',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Đường dẫn trang youtube của website.',
            ],
            // ---NHÓM: TÀI CHÍNH ---
            [
                'key' => 'tax_rate',
                'value' => '10',
                'type' => 'numeric',
                'group' => 'finance',
                'description' => 'Thuế VAT (%) áp dụng để tính toán doanh thu và lợi nhuận.',
            ],
            // ---NHÓM: HỆ THỐNG---
            [
                'key' => 'is_maintenance_mode',
                'value' => '0', // False
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'Bật chế độ bảo trì để tạm đóng cửa hàng với người dùng.',
            ],
            [
                'key' => 'items_per_page',
                'value' => '9',
                'type' => 'integer',
                'group' => 'system',
                'description' => 'Số lượng sản phẩm hiển thị trên một trang ở ngoài danh sách sản phẩm.',
            ],
        ];
        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
