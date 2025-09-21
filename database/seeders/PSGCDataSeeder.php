<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PSGCDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedRegions();
        $this->seedProvinces();
        $this->seedCities();
        $this->seedBarangays();
    }

    private function seedRegions(): void
    {
        $this->command->info('Seeding regions...');

        // Philippines regions with their PSGC codes
        $regions = [
            ['code' => '010000000', 'name' => 'Region I - Ilocos Region', 'abbreviation' => 'Region I'],
            ['code' => '020000000', 'name' => 'Region II - Cagayan Valley', 'abbreviation' => 'Region II'],
            ['code' => '030000000', 'name' => 'Region III - Central Luzon', 'abbreviation' => 'Region III'],
            ['code' => '040000000', 'name' => 'Region IV-A - CALABARZON', 'abbreviation' => 'Region IV-A'],
            ['code' => '170000000', 'name' => 'MIMAROPA', 'abbreviation' => 'MIMAROPA'],
            ['code' => '050000000', 'name' => 'Region V - Bicol Region', 'abbreviation' => 'Region V'],
            ['code' => '060000000', 'name' => 'Region VI - Western Visayas', 'abbreviation' => 'Region VI'],
            ['code' => '070000000', 'name' => 'Region VII - Central Visayas', 'abbreviation' => 'Region VII'],
            ['code' => '080000000', 'name' => 'Region VIII - Eastern Visayas', 'abbreviation' => 'Region VIII'],
            ['code' => '090000000', 'name' => 'Region IX - Zamboanga Peninsula', 'abbreviation' => 'Region IX'],
            ['code' => '100000000', 'name' => 'Region X - Northern Mindanao', 'abbreviation' => 'Region X'],
            ['code' => '110000000', 'name' => 'Region XI - Davao Region', 'abbreviation' => 'Region XI'],
            ['code' => '120000000', 'name' => 'Region XII - SOCCSKSARGEN', 'abbreviation' => 'Region XII'],
            ['code' => '160000000', 'name' => 'Region XIII - Caraga', 'abbreviation' => 'Caraga'],
            ['code' => '130000000', 'name' => 'NCR - National Capital Region', 'abbreviation' => 'NCR'],
            ['code' => '140000000', 'name' => 'CAR - Cordillera Administrative Region', 'abbreviation' => 'CAR'],
            ['code' => '150000000', 'name' => 'BARMM - Bangsamoro Autonomous Region in Muslim Mindanao', 'abbreviation' => 'BARMM'],
        ];

        foreach ($regions as $index => $data) {
            Region::updateOrCreate(
                ['code' => substr($data['code'], 0, 2)], // Use 2-digit code for lookup
                [
                    'name' => $data['name'],
                    'psa_code' => $data['code'],
                    'psa_name' => $data['name'],
                    'psa_slug' => Str::slug($data['name']),
                    'abbreviation' => $data['abbreviation'],
                    'sort_order' => $index,
                    'is_active' => true,
                    'psa_synced_at' => now(),
                ]
            );
        }

        $this->command->info('Regions seeded successfully.');
    }

    private function seedProvinces(): void
    {
        $this->command->info('Seeding provinces...');

        // Sample provinces for NCR (Metro Manila cities as provinces for demo)
        $ncr = Region::where('abbreviation', 'NCR')->first();

        if ($ncr) {
            $provinces = [
                ['code' => '137400000', 'name' => 'NCR, First District', 'income_class' => 'Special'],
                ['code' => '137500000', 'name' => 'NCR, Second District', 'income_class' => 'Special'],
                ['code' => '137600000', 'name' => 'NCR, Third District', 'income_class' => 'Special'],
                ['code' => '137700000', 'name' => 'NCR, Fourth District', 'income_class' => 'Special'],
            ];

            foreach ($provinces as $index => $data) {
                Province::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'region_id' => $ncr->id,
                        'name' => $data['name'],
                        'psa_code' => $data['code'],
                        'psa_name' => $data['name'],
                        'psa_slug' => Str::slug($data['name']),
                        'income_class' => $data['income_class'],
                        'sort_order' => $index,
                        'is_active' => true,
                        'psa_synced_at' => now(),
                    ]
                );
            }
        }

        // Sample provinces for Region III
        $region3 = Region::where('abbreviation', 'Region III')->first();

        if ($region3) {
            $provinces = [
                ['code' => '030800000', 'name' => 'Bataan', 'income_class' => '1st'],
                ['code' => '031400000', 'name' => 'Bulacan', 'income_class' => '1st'],
                ['code' => '034900000', 'name' => 'Nueva Ecija', 'income_class' => '1st'],
                ['code' => '035400000', 'name' => 'Pampanga', 'income_class' => '1st'],
                ['code' => '036900000', 'name' => 'Tarlac', 'income_class' => '1st'],
                ['code' => '037100000', 'name' => 'Zambales', 'income_class' => '2nd'],
                ['code' => '037700000', 'name' => 'Aurora', 'income_class' => '2nd'],
            ];

            foreach ($provinces as $index => $data) {
                Province::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'region_id' => $region3->id,
                        'name' => $data['name'],
                        'psa_code' => $data['code'],
                        'psa_name' => $data['name'],
                        'psa_slug' => Str::slug($data['name']),
                        'income_class' => $data['income_class'],
                        'sort_order' => $index,
                        'is_active' => true,
                        'psa_synced_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Provinces seeded successfully.');
    }

    private function seedCities(): void
    {
        $this->command->info('Seeding cities and municipalities...');

        // Sample cities for NCR First District
        $ncrFirst = Province::where('name', 'NCR, First District')->first();

        if ($ncrFirst) {
            $cities = [
                ['code' => '137401000', 'name' => 'Manila', 'type' => 'city', 'city_class' => 'HUC', 'income_class' => 'Special', 'is_capital' => true],
            ];

            foreach ($cities as $index => $data) {
                City::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'province_id' => $ncrFirst->id,
                        'name' => $data['name'],
                        'type' => $data['type'],
                        'psa_code' => $data['code'],
                        'psa_name' => 'City of ' . $data['name'],
                        'psa_slug' => Str::slug($data['name']),
                        'city_class' => $data['city_class'] ?? null,
                        'income_class' => $data['income_class'],
                        'is_capital' => $data['is_capital'] ?? false,
                        'sort_order' => $index,
                        'is_active' => true,
                        'psa_synced_at' => now(),
                    ]
                );
            }
        }

        // Sample cities for Bulacan
        $bulacan = Province::where('name', 'Bulacan')->first();

        if ($bulacan) {
            $cities = [
                ['code' => '031403000', 'name' => 'Malolos', 'type' => 'city', 'city_class' => 'CC', 'income_class' => '3rd', 'is_capital' => true],
                ['code' => '031405000', 'name' => 'Meycauayan', 'type' => 'city', 'city_class' => 'CC', 'income_class' => '1st'],
                ['code' => '031418000', 'name' => 'San Jose del Monte', 'type' => 'city', 'city_class' => 'CC', 'income_class' => '1st'],
                ['code' => '031401000', 'name' => 'Angat', 'type' => 'municipality', 'income_class' => '1st'],
                ['code' => '031402000', 'name' => 'Balagtas', 'type' => 'municipality', 'income_class' => '1st'],
                ['code' => '031404000', 'name' => 'Bulakan', 'type' => 'municipality', 'income_class' => '1st'],
            ];

            foreach ($cities as $index => $data) {
                City::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'province_id' => $bulacan->id,
                        'name' => $data['name'],
                        'type' => $data['type'],
                        'psa_code' => $data['code'],
                        'psa_name' => $data['type'] === 'city' ? 'City of ' . $data['name'] : $data['name'],
                        'psa_slug' => Str::slug($data['name']),
                        'city_class' => $data['city_class'] ?? null,
                        'income_class' => $data['income_class'],
                        'is_capital' => $data['is_capital'] ?? false,
                        'sort_order' => $index,
                        'is_active' => true,
                        'psa_synced_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Cities and municipalities seeded successfully.');
    }

    private function seedBarangays(): void
    {
        $this->command->info('Seeding barangays...');

        // Sample barangays for Malolos City
        $malolos = City::where('name', 'Malolos')->first();

        if ($malolos) {
            $barangays = [
                ['code' => '0314030001', 'name' => 'Anilao', 'urban_rural' => 'urban'],
                ['code' => '0314030002', 'name' => 'Atlag', 'urban_rural' => 'rural'],
                ['code' => '0314030003', 'name' => 'Babatnin', 'urban_rural' => 'urban'],
                ['code' => '0314030004', 'name' => 'Bagna', 'urban_rural' => 'rural'],
                ['code' => '0314030005', 'name' => 'Bagong Bayan', 'urban_rural' => 'urban'],
                ['code' => '0314030006', 'name' => 'Balayong', 'urban_rural' => 'rural'],
                ['code' => '0314030007', 'name' => 'Balite', 'urban_rural' => 'rural'],
                ['code' => '0314030008', 'name' => 'Bangkal', 'urban_rural' => 'urban'],
            ];

            foreach ($barangays as $index => $data) {
                Barangay::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'city_id' => $malolos->id,
                        'name' => $data['name'],
                        'psa_code' => $data['code'],
                        'psa_name' => $data['name'],
                        'psa_slug' => Str::slug($data['name']),
                        'urban_rural' => $data['urban_rural'],
                        'sort_order' => $index,
                        'is_active' => true,
                        'psa_synced_at' => now(),
                    ]
                );
            }
        }

        // Sample barangays for Manila
        $manila = City::where('name', 'Manila')->first();

        if ($manila) {
            $barangays = [
                ['code' => '1374010001', 'name' => 'Binondo', 'urban_rural' => 'urban'],
                ['code' => '1374010002', 'name' => 'Ermita', 'urban_rural' => 'urban'],
                ['code' => '1374010003', 'name' => 'Intramuros', 'urban_rural' => 'urban'],
                ['code' => '1374010004', 'name' => 'Malate', 'urban_rural' => 'urban'],
                ['code' => '1374010005', 'name' => 'Paco', 'urban_rural' => 'urban'],
                ['code' => '1374010006', 'name' => 'Pandacan', 'urban_rural' => 'urban'],
                ['code' => '1374010007', 'name' => 'Quiapo', 'urban_rural' => 'urban'],
                ['code' => '1374010008', 'name' => 'Sampaloc', 'urban_rural' => 'urban'],
            ];

            foreach ($barangays as $index => $data) {
                Barangay::updateOrCreate(
                    ['code' => $data['code']],
                    [
                        'city_id' => $manila->id,
                        'name' => $data['name'],
                        'psa_code' => $data['code'],
                        'psa_name' => $data['name'],
                        'psa_slug' => Str::slug($data['name']),
                        'urban_rural' => $data['urban_rural'],
                        'sort_order' => $index,
                        'is_active' => true,
                        'psa_synced_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Barangays seeded successfully.');
    }
}