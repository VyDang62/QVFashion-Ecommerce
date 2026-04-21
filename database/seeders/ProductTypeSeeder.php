<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('product_types')->insert([
            ['id' => 1, 'type_name' => 'Quần áo', 'slug' => Str::slug('Quần áo'), 'created_at' => now()],
            ['id' => 2, 'type_name' => 'Giày', 'slug' => Str::slug('Giày'), 'created_at' => now()],
            ['id' => 3, 'type_name' => 'Túi xách', 'slug' => Str::slug('Túi xách'), 'created_at' => now()],
            ['id' => 4, 'type_name' => 'Phụ kiện', 'slug' => Str::slug('Phụ kiện'), 'created_at' => now()],
            ['id' => 5, 'type_name' => 'Sắc đẹp', 'slug' => Str::slug('Sắc đẹp'), 'created_at' => now()],
        ]);
    }
}
