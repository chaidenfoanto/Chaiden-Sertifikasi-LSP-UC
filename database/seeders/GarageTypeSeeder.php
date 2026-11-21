<?php

namespace Database\Seeders;

use App\Models\GarageType;
use Illuminate\Database\Seeder;

/**
 * Seeds the three fixed garage types without prices (set by admin later).
 */
class GarageTypeSeeder extends Seeder
{
    /**
     * Create the garage types if they do not exist yet.
     */
    public function run(): void
    {
        $types = [
            ['name' => GarageType::BICYCLE, 'code' => 'S'],
            ['name' => GarageType::CAR, 'code' => 'C'],
            ['name' => GarageType::ELECTRIC_CAR, 'code' => 'E'],
        ];

        foreach ($types as $type) {
            GarageType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}