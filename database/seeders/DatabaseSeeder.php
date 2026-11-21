<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Entry point that runs all application seeders.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application database.
     */
    public function run(): void
    {
        $this->call([
            GarageTypeSeeder::class,
            AdminSeeder::class,
        ]);
    }
}