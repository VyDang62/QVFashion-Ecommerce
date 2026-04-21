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
        // User::factory(10)->create();
        User::factory()->create([
            'full_name' => 'admin',
            'email' => 'admin@example.com',
        ]);
        $this->call([
            RolesAndPermissionsSeeder::class,
            ProductTypeSeeder::class,
            ProductSeeder::class,
            SettingSeeder::class,
            BannerSeeder::class,
            PageSeeder::class,
        ]);
        
    }
}
