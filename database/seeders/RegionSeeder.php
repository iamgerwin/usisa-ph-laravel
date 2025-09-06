<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Northern Mindanao Region based on sample JSON
        $region = Region::create([
            'code' => '100000000',
            'name' => 'Northern Mindanao',
            'abbreviation' => 'Region X',
            'sort_order' => 10,
        ]);

        // Create Misamis Oriental Province
        $province = Province::create([
            'region_id' => $region->id,
            'code' => '104300000',
            'name' => 'Misamis Oriental',
            'abbreviation' => 'MisOr',
            'sort_order' => 1,
        ]);

        // Create Cagayan de Oro City
        $city = City::create([
            'province_id' => $province->id,
            'code' => '104305000',
            'name' => 'Cagayan de Oro City',
            'type' => 'city',
            'zip_code' => '9000',
            'sort_order' => 1,
        ]);

        // Create Lumbia Barangay
        Barangay::create([
            'city_id' => $city->id,
            'code' => '104305054',
            'name' => 'Lumbia',
            'sort_order' => 1,
        ]);

        // Add a few more regions for demo
        Region::create([
            'code' => '030000000',
            'name' => 'Central Luzon',
            'abbreviation' => 'Region III',
            'sort_order' => 3,
        ]);

        Region::create([
            'code' => '040000000',
            'name' => 'CALABARZON',
            'abbreviation' => 'Region IV-A',
            'sort_order' => 4,
        ]);
    }
}
