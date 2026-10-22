<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Tidak memakai WithoutModelEvents: event model dibutuhkan untuk mengisi slug otomatis.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            SuperAdminSeeder::class,
            DefaultHomePageSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(DemoContentSeeder::class);
        }
    }
}
