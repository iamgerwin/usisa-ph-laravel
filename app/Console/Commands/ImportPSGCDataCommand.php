<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportPSGCDataCommand extends Command
{
    protected $signature = 'psgc:import
                            {--type=all : Type to import (regions|provinces|cities|municipalities|barangays|all)}
                            {--clear : Clear existing data before import}';

    protected $description = 'Import PSGC data from GitLab API';

    private array $apiEndpoints = [
        'regions' => 'https://psgc.gitlab.io/api/regions.json',
        'provinces' => 'https://psgc.gitlab.io/api/provinces.json',
        'cities' => 'https://psgc.gitlab.io/api/cities.json',
        'municipalities' => 'https://psgc.gitlab.io/api/municipalities.json',
        'barangays' => 'https://psgc.gitlab.io/api/barangays.json',
    ];

    public function handle(): int
    {
        $type = $this->option('type');
        $clear = $this->option('clear');

        if ($clear && $this->confirm('This will delete all existing geographic data. Are you sure?')) {
            $this->clearData();
        }

        if ($type === 'all') {
            $this->importRegions();
            $this->importProvinces();
            $this->importCities();
            $this->importMunicipalities();
            $this->importBarangays();
        } else {
            $method = 'import' . ucfirst($type);
            if (method_exists($this, $method)) {
                $this->$method();
            } else {
                $this->error("Invalid type: {$type}");
                return Command::FAILURE;
            }
        }

        $this->info('Import completed successfully!');
        return Command::SUCCESS;
    }

    private function clearData(): void
    {
        $this->info('Clearing existing data...');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        Barangay::truncate();
        City::truncate();
        Province::truncate();
        Region::truncate();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        $this->info('Data cleared.');
    }

    private function importRegions(): void
    {
        $this->info('Importing regions...');
        $response = Http::get($this->apiEndpoints['regions']);

        if (!$response->successful()) {
            $this->error('Failed to fetch regions data');
            return;
        }

        $data = $response->json();
        $bar = $this->output->createProgressBar(count($data));

        foreach ($data as $item) {
            Region::updateOrCreate(
                ['code' => substr($item['code'], 0, 2)],
                [
                    'name' => $item['name'],
                    'region_name' => $item['regionName'] ?? null,
                    'psa_code' => $item['psgc10DigitCode'],
                    'psa_name' => $item['name'],
                    'psa_slug' => Str::slug($item['name']),
                    'island_group_code' => $item['islandGroupCode'] ?? null,
                    'abbreviation' => Str::limit($item['name'], 20, ''),
                    'is_active' => true,
                    'psa_synced_at' => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Regions imported: ' . count($data));
    }

    private function importProvinces(): void
    {
        $this->info('Importing provinces...');
        $response = Http::get($this->apiEndpoints['provinces']);

        if (!$response->successful()) {
            $this->error('Failed to fetch provinces data');
            return;
        }

        $data = $response->json();
        $bar = $this->output->createProgressBar(count($data));

        foreach ($data as $item) {
            $region = Region::where('code', substr($item['regionCode'], 0, 2))
                ->orWhere('psa_code', $item['regionCode'])
                ->first();

            if (!$region) {
                $this->warn("Region not found for province: {$item['name']} ({$item['regionCode']})");
                continue;
            }

            Province::updateOrCreate(
                ['code' => $item['code']],
                [
                    'region_id' => $region->id,
                    'name' => $item['name'],
                    'psa_code' => $item['psgc10DigitCode'],
                    'psa_name' => $item['name'],
                    'psa_slug' => Str::slug($item['name']),
                    'island_group_code' => $item['islandGroupCode'] ?? null,
                    'is_active' => true,
                    'psa_synced_at' => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Provinces imported: ' . count($data));
    }

    private function importCities(): void
    {
        $this->info('Importing cities...');
        $response = Http::get($this->apiEndpoints['cities']);

        if (!$response->successful()) {
            $this->error('Failed to fetch cities data');
            return;
        }

        $data = $response->json();
        $bar = $this->output->createProgressBar(count($data));

        foreach ($data as $item) {
            $province = null;

            if (!empty($item['provinceCode'])) {
                $province = Province::where('code', $item['provinceCode'])
                    ->orWhere('psa_code', $item['provinceCode'])
                    ->first();
            } elseif (!empty($item['districtCode'])) {
                // NCR cities - use district as pseudo-province
                $province = Province::where('code', $item['districtCode'])
                    ->orWhere('district_code', $item['districtCode'])
                    ->first();
            }

            if (!$province && !empty($item['regionCode'])) {
                // Create a special province for independent cities
                $region = Region::where('psa_code', $item['regionCode'])->first();
                if ($region) {
                    $province = Province::firstOrCreate(
                        ['code' => $item['regionCode'] . '99'],
                        [
                            'region_id' => $region->id,
                            'name' => 'Independent Cities',
                            'is_active' => true,
                        ]
                    );
                }
            }

            if (!$province) {
                $this->warn("Province not found for city: {$item['name']}");
                continue;
            }

            City::updateOrCreate(
                ['code' => $item['code']],
                [
                    'province_id' => $province->id,
                    'name' => $item['name'],
                    'type' => 'city',
                    'psa_code' => $item['psgc10DigitCode'],
                    'psa_name' => $item['name'],
                    'psa_slug' => Str::slug($item['name'] . '-' . $item['code']),
                    'old_name' => $item['oldName'] ?? null,
                    'is_capital' => $item['isCapital'] ?? false,
                    'island_group_code' => $item['islandGroupCode'] ?? null,
                    'district_code' => $item['districtCode'] ?? null,
                    'is_active' => true,
                    'psa_synced_at' => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Cities imported: ' . count($data));
    }

    private function importMunicipalities(): void
    {
        $this->info('Importing municipalities...');
        $response = Http::get($this->apiEndpoints['municipalities']);

        if (!$response->successful()) {
            $this->error('Failed to fetch municipalities data');
            return;
        }

        $data = $response->json();
        $bar = $this->output->createProgressBar(count($data));

        foreach ($data as $item) {
            $province = Province::where('code', $item['provinceCode'])
                ->orWhere('psa_code', $item['provinceCode'])
                ->first();

            if (!$province) {
                $this->warn("Province not found for municipality: {$item['name']} ({$item['provinceCode']})");
                continue;
            }

            City::updateOrCreate(
                ['code' => $item['code']],
                [
                    'province_id' => $province->id,
                    'name' => $item['name'],
                    'type' => 'municipality',
                    'psa_code' => $item['psgc10DigitCode'],
                    'psa_name' => $item['name'],
                    'psa_slug' => Str::slug($item['name'] . '-' . $item['code']),
                    'old_name' => $item['oldName'] ?? null,
                    'is_capital' => $item['isCapital'] ?? false,
                    'island_group_code' => $item['islandGroupCode'] ?? null,
                    'district_code' => $item['districtCode'] ?? null,
                    'is_active' => true,
                    'psa_synced_at' => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Municipalities imported: ' . count($data));
    }

    private function importBarangays(): void
    {
        $this->info('Importing barangays...');
        $response = Http::timeout(60)->get($this->apiEndpoints['barangays']);

        if (!$response->successful()) {
            $this->error('Failed to fetch barangays data');
            return;
        }

        $data = $response->json();
        $this->info('Total barangays to import: ' . count($data));

        // Process in chunks for better performance
        $chunks = array_chunk($data, 1000);
        $chunkBar = $this->output->createProgressBar(count($chunks));

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $item) {
                    $city = null;

                    // Try to find parent city or municipality
                    if (!empty($item['cityCode'])) {
                        $city = City::where('code', $item['cityCode'])
                            ->orWhere('psa_code', $item['cityCode'])
                            ->first();
                    }

                    if (!$city && !empty($item['municipalityCode'])) {
                        $city = City::where('code', $item['municipalityCode'])
                            ->orWhere('psa_code', $item['municipalityCode'])
                            ->first();
                    }

                    if (!$city) {
                        continue; // Skip if no parent found
                    }

                    Barangay::updateOrCreate(
                        ['code' => $item['code']],
                        [
                            'city_id' => $city->id,
                            'name' => $item['name'],
                            'psa_code' => $item['psgc10DigitCode'],
                            'psa_name' => $item['name'],
                            'psa_slug' => Str::slug($item['name'] . '-' . $item['code']),
                            'old_name' => $item['oldName'] ?? null,
                            'island_group_code' => $item['islandGroupCode'] ?? null,
                            'district_code' => $item['districtCode'] ?? null,
                            'sub_municipality_code' => $item['subMunicipalityCode'] ?? null,
                            'is_active' => true,
                            'psa_synced_at' => now(),
                        ]
                    );
                }

                DB::commit();
                $chunkBar->advance();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error processing chunk {$chunkIndex}: " . $e->getMessage());
            }
        }

        $chunkBar->finish();
        $this->newLine();
        $this->info('Barangays imported: ' . count($data));
    }
}