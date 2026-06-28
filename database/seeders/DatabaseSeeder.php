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
            'name' => 'Super Admin',
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'password' => bcrypt('password'), // Change this to a secure password in production
            'role' => 'admin',
        ]);

        // Call the Seeders
        $this->call([
            SystemConfigSeeder::class,
            LanguageSeeder::class,
            //            CategorySeeder::class,
            //            ProductSeeder::class,
            DeliveryZoneSeeder::class,
        ]);
    }
}
