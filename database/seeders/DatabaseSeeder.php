<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            ProductTypeSeeder::class,
            ProductSeeder::class,
            OrderRatingSeeder::class,
            SettingSeeder::class,
            BannerSeeder::class,
            PageSeeder::class,
        ]);
        User::factory()->create([
            'full_name' => 'admin',
            'email' => 'admin@example.com',
        ]);
    }
}
