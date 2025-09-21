<?php

namespace App\Console\Commands;

use App\Services\Scrapers\DimeScraperStrategy;
use App\Models\ScraperSource;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Factory as Faker;

class TestDimeScraperCommand extends Command
{
    protected $signature = 'dime:test-scraper
                            {--count=50 : Number of mock projects to generate}
                            {--with-errors : Include some malformed data to test error handling}
                            {--with-unknowns : Include unknown fields to test adaptability}';

    protected $description = 'Test DIME scraper with mock data to handle unforeseen scenarios';

    protected DimeScraperStrategy $strategy;
    protected $faker;

    public function handle()
    {
        $this->faker = Faker::create('en_PH');
        $count = (int) $this->option('count');
        $withErrors = $this->option('with-errors');
        $withUnknowns = $this->option('with-unknowns');

        $this->info('Testing DIME scraper with mock data...');
        $this->info("Generating {$count} mock projects");

        // Initialize scraper components
        $source = $this->initializeSource();
        $this->strategy = new DimeScraperStrategy($source);

        $successCount = 0;
        $errorCount = 0;
        $unknownFields = [];

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $mockData = $this->generateMockProject($i, $withErrors, $withUnknowns);

            try {
                // Test validation
                if (!$this->strategy->validateData($mockData)) {
                    $this->logError('Validation failed', $mockData);
                    $errorCount++;
                    continue;
                }

                // Test data processing
                $processed = $this->strategy->processData($mockData);

                // Test geographic alignment
                $processed = $this->testGeographicAlignment($processed);

                // Detect unknown fields
                if ($withUnknowns) {
                    $unknowns = $this->detectUnknownFields($mockData, $processed);
                    foreach ($unknowns as $field) {
                        if (!in_array($field, $unknownFields)) {
                            $unknownFields[] = $field;
                        }
                    }
                }

                // Test database insertion
                DB::beginTransaction();
                try {
                    $project = Project::create($processed);
                    DB::rollBack(); // Don't actually save in test mode
                    $successCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->logError('Database error: ' . $e->getMessage(), $processed);
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->logError('Processing error: ' . $e->getMessage(), $mockData);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayTestResults($successCount, $errorCount, $unknownFields);

        return Command::SUCCESS;
    }

    protected function generateMockProject(int $index, bool $withErrors, bool $withUnknowns): array
    {
        $regions = ['NCR', 'Region I', 'Region II', 'Region III', 'CALABARZON', 'MIMAROPA', 'Region V',
                    'Region VI', 'Region VII', 'Region VIII', 'Region IX', 'Region X', 'Region XI',
                    'Region XII', 'CARAGA', 'CAR', 'BARMM'];

        $statuses = ['Ongoing', 'Completed', 'Pending', 'Suspended', 'For Bidding', 'Under Procurement'];

        $project = [
            'id' => 'DIME-2024-' . str_pad($index, 6, '0', STR_PAD_LEFT),
            'projectName' => $this->faker->sentence(6),
            'projectCode' => 'PRJ-' . $this->faker->bothify('##??##'),
            'description' => $this->faker->paragraph(3),
            'projectImageUrl' => $this->faker->imageUrl(800, 600, 'construction'),
            'streetAddress' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'barangay' => 'Barangay ' . $this->faker->firstName,
            'province' => $this->faker->state,
            'region' => $this->faker->randomElement($regions),
            'zipCode' => $this->faker->postcode,
            'country' => 'Philippines',
            'latitude' => $this->faker->latitude(4.5, 21.5),
            'longitude' => $this->faker->longitude(116.0, 127.0),
            'status' => $this->faker->randomElement($statuses),
            'publicationStatus' => 'Published',
            'cost' => $this->faker->randomFloat(2, 100000, 100000000),
            'utilizedAmount' => $this->faker->randomFloat(2, 0, 50000000),
            'dateStarted' => $this->faker->date('Y-m-d', '-2 years'),
            'contractCompletionDate' => $this->faker->date('Y-m-d', '+2 years'),
            'updatesCount' => $this->faker->numberBetween(0, 50),
            'implementingOffices' => [
                [
                    'id' => $this->faker->uuid,
                    'name' => $this->faker->company . ' Department',
                    'nameAbbreviation' => strtoupper($this->faker->lexify('???')),
                    'logoUrl' => $this->faker->imageUrl(200, 200, 'logo'),
                ]
            ],
            'contractors' => [
                [
                    'id' => $this->faker->uuid,
                    'name' => $this->faker->company . ' Construction Corp.',
                    'nameAbbreviation' => strtoupper($this->faker->lexify('???')),
                ]
            ],
            'sourceOfFunds' => [
                [
                    'id' => $this->faker->uuid,
                    'name' => $this->faker->randomElement(['GAA', 'World Bank', 'ADB', 'JICA', 'Local Funds']),
                ]
            ],
            'program' => [
                'id' => $this->faker->uuid,
                'programName' => $this->faker->randomElement(['Build Build Build', 'DPWH Infrastructure', 'DOH Health Facilities']),
                'nameAbbreviation' => strtoupper($this->faker->lexify('???')),
            ],
        ];

        // Add errors if requested
        if ($withErrors && $index % 5 === 0) {
            $errorType = $index % 15;
            switch ($errorType) {
                case 0:
                    // Missing required field
                    unset($project['projectName']);
                    break;
                case 5:
                    // Invalid coordinate
                    $project['latitude'] = 'invalid';
                    $project['longitude'] = 999;
                    break;
                case 10:
                    // Malformed date
                    $project['dateStarted'] = 'not-a-date';
                    break;
            }
        }

        // Add unknown fields if requested
        if ($withUnknowns && $index % 3 === 0) {
            $project['newField1'] = $this->faker->word;
            $project['unexpectedData'] = [
                'subfield1' => $this->faker->sentence,
                'subfield2' => $this->faker->numberBetween(1, 100),
            ];
            $project['futureFeature'] = $this->faker->boolean;
            $project['customMetric'] = $this->faker->randomFloat(2, 0, 100);
        }

        // Occasionally use nested structure (Next.js response format)
        if ($index % 4 === 0) {
            return [
                'pageProps' => [
                    'project' => $project
                ]
            ];
        }

        return $project;
    }

    protected function testGeographicAlignment(array $data): array
    {
        // Simulate geographic matching
        $regions = \App\Models\Region::pluck('name', 'id')->toArray();
        $provinces = \App\Models\Province::pluck('name', 'id')->toArray();
        $cities = \App\Models\City::limit(100)->pluck('name', 'id')->toArray();

        // Try to match region
        foreach ($regions as $id => $name) {
            if (stripos($data['region_name'] ?? '', $name) !== false) {
                $data['region_id'] = $id;
                break;
            }
        }

        // Try to match province
        foreach ($provinces as $id => $name) {
            if (stripos($data['province_name'] ?? '', $name) !== false) {
                $data['province_id'] = $id;
                break;
            }
        }

        // Try to match city
        foreach ($cities as $id => $name) {
            if (stripos($data['city_name'] ?? '', $name) !== false) {
                $data['city_id'] = $id;
                break;
            }
        }

        return $data;
    }

    protected function detectUnknownFields(array $original, array $processed): array
    {
        $knownFields = [
            'id', 'projectName', 'projectCode', 'description', 'projectImageUrl',
            'streetAddress', 'city', 'cityCode', 'barangay', 'barangayCode',
            'province', 'provinceCode', 'region', 'regionCode', 'zipCode',
            'country', 'state', 'latitude', 'longitude', 'status', 'publicationStatus',
            'cost', 'utilizedAmount', 'dateStarted', 'actualDateStarted',
            'contractCompletionDate', 'actualContractCompletionDate', 'asOfDate',
            'lastUpdatedProjectCost', 'updatesCount', 'implementingOffices',
            'contractors', 'sourceOfFunds', 'program', 'resources', 'progresses',
            'createdAt', 'updatedAt', 'pageProps'
        ];

        $unknowns = [];
        foreach ($original as $key => $value) {
            if (!in_array($key, $knownFields)) {
                $unknowns[] = $key;
            }
        }

        return $unknowns;
    }

    protected function logError(string $message, array $data): void
    {
        Log::error('DIME Test Scraper Error', [
            'message' => $message,
            'data' => array_slice($data, 0, 5), // Limit logged data
        ]);
    }

    protected function displayTestResults(int $success, int $errors, array $unknownFields): void
    {
        $this->info('=== DIME Scraper Test Results ===');
        $this->newLine();

        $total = $success + $errors;
        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $total],
                ['Successful', $success],
                ['Errors', $errors],
                ['Success Rate', "{$successRate}%"],
            ]
        );

        if (!empty($unknownFields)) {
            $this->newLine();
            $this->warn('Unknown fields detected:');
            foreach ($unknownFields as $field) {
                $this->line("  - {$field}");
            }
            $this->newLine();
            $this->info('Consider adding these fields to the metadata or creating new model attributes.');
        }

        if ($errors > 0) {
            $this->newLine();
            $this->error("There were {$errors} errors during testing. Check the logs for details.");
            $this->info('Common issues to address:');
            $this->line('  - Missing required fields');
            $this->line('  - Invalid data types (dates, coordinates, numbers)');
            $this->line('  - Unexpected data structures');
        }
    }

    protected function initializeSource(): ScraperSource
    {
        return ScraperSource::firstOrCreate(
            ['code' => 'dime'],
            [
                'name' => 'DIME.gov.ph',
                'base_url' => 'https://www.dime.gov.ph',
                'api_url' => 'https://www.dime.gov.ph/api/projects',
                'is_active' => true,
                'scraper_class' => DimeScraperStrategy::class,
                'version' => '1.0',
            ]
        );
    }
}