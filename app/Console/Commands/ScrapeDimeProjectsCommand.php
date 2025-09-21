<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\ScraperJob;
use App\Models\ScraperSource;
use App\Models\ImplementingOffice;
use App\Models\Contractor;
use App\Models\SourceOfFund;
use App\Models\Program;
use App\Enums\ScraperJobStatus;
use App\Services\Scrapers\DimeScraperStrategy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ScrapeDimeProjectsCommand extends Command
{
    protected $signature = 'dime:scrape-projects
                            {--limit=20000 : Number of projects to scrape}
                            {--offset=0 : Starting offset for pagination}
                            {--chunk=50 : Number of projects per API request}
                            {--delay=1 : Delay in seconds between requests}
                            {--retry=3 : Number of retry attempts for failed requests}
                            {--test : Run in test mode with only 10 projects}
                            {--dry-run : Preview without saving to database}';

    protected $description = 'Scrape projects from DIME.gov.ph API with geographic alignment';

    protected ScraperSource $source;
    protected ScraperJob $job;
    protected DimeScraperStrategy $strategy;

    protected array $stats = [
        'total_fetched' => 0,
        'total_saved' => 0,
        'total_updated' => 0,
        'total_skipped' => 0,
        'geographic_matched' => 0,
        'geographic_partial' => 0,
        'geographic_unmatched' => 0,
        'errors' => 0,
        'api_calls' => 0,
    ];

    protected array $regionCache = [];
    protected array $provinceCache = [];
    protected array $cityCache = [];
    protected array $barangayCache = [];

    public function handle()
    {
        // Increase memory limit for this command
        ini_set('memory_limit', '512M');

        $this->info('Starting DIME.gov.ph project scraping with geographic alignment...');

        $limit = $this->option('test') ? 10 : (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $chunkSize = (int) $this->option('chunk');
        $delay = (int) $this->option('delay');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be saved to database');
        }

        try {
            $this->initializeSource();
            $this->initializeJob($limit, $offset);
            $this->strategy = new DimeScraperStrategy($this->source);
            $this->loadGeographicCache();

            $this->info("Target: {$limit} projects");
            $this->info("Starting from offset: {$offset}");
            $this->info("Chunk size: {$chunkSize}");

            $progressBar = $this->output->createProgressBar($limit);
            $progressBar->start();

            $currentOffset = $offset;
            $totalProcessed = 0;

            while ($totalProcessed < $limit) {
                $currentChunk = min($chunkSize, $limit - $totalProcessed);

                try {
                    $projects = $this->fetchProjects($currentOffset, $currentChunk);

                    if (empty($projects)) {
                        $this->newLine();
                        $this->warn("No more projects available at offset {$currentOffset}");
                        break;
                    }

                    foreach ($projects as $projectData) {
                        if ($totalProcessed >= $limit) break;

                        $this->processProject($projectData, $isDryRun);
                        $totalProcessed++;
                        $progressBar->advance();

                        if (!$isDryRun && $this->job) {
                            $this->job->current_id = $currentOffset + $totalProcessed;
                            $this->job->save();
                        }
                    }

                    $currentOffset += $currentChunk;

                    // Rate limiting
                    if ($delay > 0) {
                        sleep($delay);
                    }

                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error('DIME scraping chunk error', [
                        'offset' => $currentOffset,
                        'chunk_size' => $currentChunk,
                        'error' => $e->getMessage()
                    ]);

                    // Continue with next chunk
                    $currentOffset += $currentChunk;
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            if (!$isDryRun && $this->job) {
                $this->job->markAsCompleted();
            }

            $this->displayStatistics();

        } catch (\Exception $e) {
            $this->error("Scraping failed: {$e->getMessage()}");

            if ($this->job ?? null) {
                $this->job->markAsFailed($e->getMessage());
            }

            Log::error('DIME scraping command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function initializeSource(): void
    {
        $this->source = ScraperSource::firstOrCreate(
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

    protected function initializeJob(int $limit, int $offset): void
    {
        $this->job = ScraperJob::create([
            'source_id' => $this->source->id,
            'start_id' => $offset,
            'end_id' => $offset + $limit,
            'current_id' => $offset,
            'chunk_size' => (int) $this->option('chunk'),
            'status' => ScraperJobStatus::RUNNING,
            'triggered_by' => 'command',
            'notes' => "Scraping {$limit} projects from DIME API",
        ]);
    }

    protected function loadGeographicCache(): void
    {
        $this->info('Loading geographic data cache...');

        // Cache regions - only essential fields
        Region::select('id', 'name', 'code', 'psa_code', 'psa_name', 'abbreviation')
            ->get()
            ->each(function ($region) {
                $this->regionCache[strtolower($region->name)] = $region;
                if ($region->psa_name) {
                    $this->regionCache[strtolower($region->psa_name)] = $region;
                }
                if ($region->abbreviation) {
                    $this->regionCache[strtolower($region->abbreviation)] = $region;
                }
                $this->regionCache[$region->code] = $region;
            });

        // Cache provinces - only essential fields
        Province::select('id', 'name', 'code', 'psa_code', 'psa_name', 'region_id')
            ->get()
            ->each(function ($province) {
                $this->provinceCache[strtolower($province->name)] = $province;
                if ($province->psa_name) {
                    $this->provinceCache[strtolower($province->psa_name)] = $province;
                }
                $this->provinceCache[$province->code] = $province;
            });

        // Cache cities - only essential fields
        City::select('id', 'name', 'code', 'psa_code', 'psa_name', 'province_id')
            ->limit(5000) // Limit to prevent memory issues
            ->get()
            ->each(function ($city) {
                $this->cityCache[strtolower($city->name)] = $city;
                if ($city->psa_name) {
                    $this->cityCache[strtolower($city->psa_name)] = $city;
                }
                $this->cityCache[$city->code] = $city;
            });

        // Skip barangay caching for now - will query directly
        // Barangays are too many to cache efficiently

        $this->info(sprintf(
            'Cached: %d regions, %d provinces, %d cities',
            count($this->regionCache) / 2, // Approximate unique count
            count($this->provinceCache) / 2,
            count($this->cityCache) / 2
        ));
    }

    protected function fetchProjects(int $offset, int $limit): array
    {
        $maxRetries = (int) $this->option('retry');
        $attempts = 0;

        // Try multiple endpoint patterns
        $endpoints = [
            'https://www.dime.gov.ph/api/projects',
            'https://api.dime.gov.ph/projects',
            'https://www.dime.gov.ph/api/v1/projects',
            'https://www.dime.gov.ph/_api/projects',
        ];

        while ($attempts < $maxRetries) {
            try {
                $this->stats['api_calls']++;

                // Try each endpoint
                foreach ($endpoints as $endpoint) {
                    try {
                        $response = Http::timeout(30)
                            ->withHeaders([
                                'Accept' => 'application/json',
                                'User-Agent' => 'Mozilla/5.0 (compatible; USISA-Scraper/1.0)',
                            ])
                            ->get($endpoint, [
                                'limit' => $limit,
                                'offset' => $offset,
                                'sort' => 'updatedAt:desc'
                            ]);

                        if ($response->successful()) {
                            $data = $response->json();

                            // Handle different response structures
                            $projects = $data['data'] ?? $data['projects'] ?? $data['items'] ?? $data;

                            if (is_array($projects) && !empty($projects)) {
                                $this->stats['total_fetched'] += count($projects);
                                $this->info("Successfully fetched from: {$endpoint}");
                                return $projects;
                            }
                        }

                        // Check if site is under maintenance
                        if ($response->status() === 503 || str_contains($response->body(), 'maintenance')) {
                            $this->warn("DIME website is under maintenance. Please try again later.");
                            return [];
                        }

                    } catch (\Exception $endpointException) {
                        // Try next endpoint
                        continue;
                    }
                }

                throw new \Exception("All API endpoints failed. Last status: " . ($response->status() ?? 'unknown'));

            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    $this->error("Failed after {$maxRetries} attempts: " . $e->getMessage());

                    // Check if we should try web scraping as fallback
                    if ($this->confirm('API is not available. Would you like to try web scraping instead?')) {
                        return $this->fallbackWebScraping($offset, $limit);
                    }

                    throw $e;
                }

                $this->warn("Retry {$attempts}/{$maxRetries}: " . $e->getMessage());
                sleep(2 * $attempts); // Exponential backoff
            }
        }

        return [];
    }

    /**
     * Fallback to web scraping when API is not available
     */
    protected function fallbackWebScraping(int $offset, int $limit): array
    {
        $this->warn('Attempting web scraping fallback...');

        try {
            // Try to scrape the projects listing page
            $response = Http::timeout(60)
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get('https://www.dime.gov.ph/projects', [
                    'page' => floor($offset / $limit) + 1,
                    'per_page' => $limit,
                ]);

            if ($response->successful()) {
                // Extract Next.js data from HTML
                $html = $response->body();

                if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                    $jsonData = json_decode($matches[1], true);

                    if (isset($jsonData['props']['pageProps']['projects'])) {
                        $projects = $jsonData['props']['pageProps']['projects'];
                        $this->info('Successfully extracted ' . count($projects) . ' projects from HTML');
                        return $projects;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Web scraping fallback failed: ' . $e->getMessage());
        }

        return [];
    }

    protected function processProject(array $projectData, bool $isDryRun): void
    {
        try {
            DB::beginTransaction();

            // Process through strategy
            if (!$this->strategy->validateData($projectData)) {
                $this->stats['total_skipped']++;
                DB::rollBack();
                return;
            }

            $processedData = $this->strategy->processData($projectData);

            // Align geographic data
            $processedData = $this->alignGeographicData($processedData);

            // Check if project exists
            $existingProject = Project::where('external_id', $processedData['external_id'])
                ->where('external_source', 'dime')
                ->first();

            if (!$isDryRun) {
                if ($existingProject) {
                    $existingProject->update($processedData);
                    $this->stats['total_updated']++;
                } else {
                    $project = Project::create($processedData);
                    $this->stats['total_saved']++;
                }

                // Process related entities
                $this->processRelatedEntities($existingProject ?? $project, $processedData['metadata']);
            } else {
                if ($existingProject) {
                    $this->stats['total_updated']++;
                } else {
                    $this->stats['total_saved']++;
                }
            }

            DB::commit();

            // Update job statistics
            if (!$isDryRun && $this->job) {
                if ($existingProject) {
                    $this->job->update_count++;
                } else {
                    $this->job->create_count++;
                }
                $this->job->save();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;

            Log::error('Failed to process DIME project', [
                'project_id' => $projectData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            if ($this->job) {
                $this->job->error_count++;
                $this->job->save();
            }
        }
    }

    protected function alignGeographicData(array $data): array
    {
        $matched = 0;

        // Match Region
        if (!empty($data['region_name']) || !empty($data['region_code'])) {
            $region = $this->findRegion($data['region_name'], $data['region_code']);
            if ($region) {
                $data['region_id'] = $region->id;
                $matched++;
            }
        }

        // Match Province
        if (!empty($data['province_name']) || !empty($data['province_code'])) {
            $province = $this->findProvince($data['province_name'], $data['province_code'], $data['region_id'] ?? null);
            if ($province) {
                $data['province_id'] = $province->id;
                // Update region if not set
                if (!isset($data['region_id']) && $province->region_id) {
                    $data['region_id'] = $province->region_id;
                }
                $matched++;
            }
        }

        // Match City
        if (!empty($data['city_name']) || !empty($data['city_code'])) {
            $city = $this->findCity($data['city_name'], $data['city_code'], $data['province_id'] ?? null);
            if ($city) {
                $data['city_id'] = $city->id;
                // Update province and region if not set
                if (!isset($data['province_id']) && $city->province_id) {
                    $data['province_id'] = $city->province_id;
                    if ($city->province && !isset($data['region_id'])) {
                        $data['region_id'] = $city->province->region_id;
                    }
                }
                $matched++;
            }
        }

        // Match Barangay
        if (!empty($data['barangay_name']) || !empty($data['barangay_code'])) {
            $barangay = $this->findBarangay($data['barangay_name'], $data['barangay_code'], $data['city_id'] ?? null);
            if ($barangay) {
                $data['barangay_id'] = $barangay->id;
                // Update city, province and region if not set
                if (!isset($data['city_id']) && $barangay->city_id) {
                    $data['city_id'] = $barangay->city_id;
                    if ($barangay->city) {
                        if (!isset($data['province_id']) && $barangay->city->province_id) {
                            $data['province_id'] = $barangay->city->province_id;
                        }
                        if (!isset($data['region_id']) && $barangay->city->province) {
                            $data['region_id'] = $barangay->city->province->region_id;
                        }
                    }
                }
                $matched++;
            }
        }

        // Update statistics
        if ($matched == 4) {
            $this->stats['geographic_matched']++;
        } elseif ($matched > 0) {
            $this->stats['geographic_partial']++;
        } else {
            $this->stats['geographic_unmatched']++;
        }

        return $data;
    }

    protected function findRegion(?string $name, ?string $code): ?Region
    {
        if ($code) {
            $region = $this->regionCache[$code] ?? null;
            if ($region) return $region;
        }

        if ($name) {
            $searchName = strtolower(trim($name));

            // Direct match
            if (isset($this->regionCache[$searchName])) {
                return $this->regionCache[$searchName];
            }

            // Fuzzy match
            foreach ($this->regionCache as $key => $region) {
                if (str_contains($searchName, $key) || str_contains($key, $searchName)) {
                    return $region;
                }
            }
        }

        return null;
    }

    protected function findProvince(?string $name, ?string $code, ?int $regionId): ?Province
    {
        if ($code) {
            $province = $this->provinceCache[$code] ?? null;
            if ($province && (!$regionId || $province->region_id == $regionId)) {
                return $province;
            }
        }

        if ($name) {
            $searchName = strtolower(trim($name));

            // Direct match
            if (isset($this->provinceCache[$searchName])) {
                $province = $this->provinceCache[$searchName];
                if (!$regionId || $province->region_id == $regionId) {
                    return $province;
                }
            }

            // Fuzzy match
            foreach ($this->provinceCache as $key => $province) {
                if (str_contains($searchName, $key) || str_contains($key, $searchName)) {
                    if (!$regionId || $province->region_id == $regionId) {
                        return $province;
                    }
                }
            }
        }

        return null;
    }

    protected function findCity(?string $name, ?string $code, ?int $provinceId): ?City
    {
        if ($code) {
            $city = $this->cityCache[$code] ?? null;
            if ($city && (!$provinceId || $city->province_id == $provinceId)) {
                return $city;
            }
        }

        if ($name) {
            $searchName = strtolower(trim($name));

            // Try with province context first
            if ($provinceId) {
                $key = $searchName . '_' . $provinceId;
                if (isset($this->cityCache[$key])) {
                    return $this->cityCache[$key];
                }
            }

            // Direct match
            if (isset($this->cityCache[$searchName])) {
                $city = $this->cityCache[$searchName];
                if (!$provinceId || $city->province_id == $provinceId) {
                    return $city;
                }
            }

            // Remove "City of" or "Municipality of" prefixes
            $cleanName = preg_replace('/^(city of |municipality of )/i', '', $searchName);
            if (isset($this->cityCache[$cleanName])) {
                $city = $this->cityCache[$cleanName];
                if (!$provinceId || $city->province_id == $provinceId) {
                    return $city;
                }
            }
        }

        return null;
    }

    protected function findBarangay(?string $name, ?string $code, ?int $cityId): ?Barangay
    {
        // For large datasets, query directly instead of using cache
        if (empty($this->barangayCache)) {
            $query = Barangay::query();

            if ($code) {
                $query->where(function ($q) use ($code) {
                    $q->where('code', $code)
                      ->orWhere('psa_code', $code);
                });
            } elseif ($name) {
                $query->where('name', 'ILIKE', '%' . trim($name) . '%');
            }

            if ($cityId) {
                $query->where('city_id', $cityId);
            }

            return $query->first();
        }

        // Use cache for smaller datasets
        if ($code) {
            $barangay = $this->barangayCache[$code] ?? null;
            if ($barangay && (!$cityId || $barangay->city_id == $cityId)) {
                return $barangay;
            }
        }

        if ($name) {
            $searchName = strtolower(trim($name));

            // Try with city context first
            if ($cityId) {
                $key = $searchName . '_' . $cityId;
                if (isset($this->barangayCache[$key])) {
                    return $this->barangayCache[$key];
                }
            }

            // Direct match
            if (isset($this->barangayCache[$searchName])) {
                $barangay = $this->barangayCache[$searchName];
                if (!$cityId || $barangay->city_id == $cityId) {
                    return $barangay;
                }
            }
        }

        return null;
    }

    protected function processRelatedEntities(Project $project, array $metadata): void
    {
        // Process implementing offices
        if (isset($metadata['implementing_offices']) && is_array($metadata['implementing_offices'])) {
            foreach ($metadata['implementing_offices'] as $officeData) {
                if (empty($officeData['name'])) continue;

                $office = ImplementingOffice::firstOrCreate(
                    ['name' => $officeData['name']],
                    [
                        'dime_id' => $officeData['id'] ?? null,
                        'name_abbreviation' => $officeData['abbreviation'] ?? null,
                        'logo_url' => $officeData['logo_url'] ?? null,
                        'is_active' => true,
                    ]
                );

                if (!$project->implementingOffices()->where('implementing_office_uuid', $office->uuid)->exists()) {
                    $project->implementingOffices()->attach($office->uuid, [
                        'is_primary' => count($project->implementingOffices) == 0
                    ]);
                }
            }
        }

        // Process contractors
        if (isset($metadata['contractors']) && is_array($metadata['contractors'])) {
            foreach ($metadata['contractors'] as $contractorData) {
                if (empty($contractorData['name'])) continue;

                $contractor = Contractor::firstOrCreate(
                    ['name' => $contractorData['name']],
                    [
                        'dime_id' => $contractorData['id'] ?? null,
                        'name_abbreviation' => $contractorData['abbreviation'] ?? null,
                        'logo_url' => $contractorData['logo_url'] ?? null,
                        'is_active' => true,
                    ]
                );

                if (!$project->contractors()->where('contractor_uuid', $contractor->uuid)->exists()) {
                    $project->contractors()->attach($contractor->uuid);
                }
            }
        }

        // Process source of funds
        if (isset($metadata['source_of_funds']) && is_array($metadata['source_of_funds'])) {
            foreach ($metadata['source_of_funds'] as $sourceData) {
                if (empty($sourceData['name'])) continue;

                $source = SourceOfFund::firstOrCreate(
                    ['name' => $sourceData['name']],
                    [
                        'dime_id' => $sourceData['id'] ?? null,
                        'name_abbreviation' => $sourceData['abbreviation'] ?? null,
                        'logo_url' => $sourceData['logo_url'] ?? null,
                        'is_active' => true,
                    ]
                );

                if (!$project->sourceOfFunds()->where('source_of_fund_uuid', $source->uuid)->exists()) {
                    $project->sourceOfFunds()->attach($source->uuid, [
                        'is_primary' => count($project->sourceOfFunds) == 0
                    ]);
                }
            }
        }
    }

    protected function displayStatistics(): void
    {
        $this->info('DIME Scraping Complete!');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['API Calls Made', number_format($this->stats['api_calls'])],
                ['Total Fetched', number_format($this->stats['total_fetched'])],
                ['New Projects Saved', number_format($this->stats['total_saved'])],
                ['Projects Updated', number_format($this->stats['total_updated'])],
                ['Projects Skipped', number_format($this->stats['total_skipped'])],
                ['Fully Geo-Matched', number_format($this->stats['geographic_matched'])],
                ['Partially Geo-Matched', number_format($this->stats['geographic_partial'])],
                ['Geo-Unmatched', number_format($this->stats['geographic_unmatched'])],
                ['Errors', number_format($this->stats['errors'])],
            ]
        );

        if ($this->stats['errors'] > 0) {
            $this->warn("There were {$this->stats['errors']} errors during scraping. Check the logs for details.");
        }

        $geoMatchRate = $this->stats['total_saved'] + $this->stats['total_updated'] > 0
            ? round(($this->stats['geographic_matched'] + $this->stats['geographic_partial']) /
                    ($this->stats['total_saved'] + $this->stats['total_updated']) * 100, 2)
            : 0;

        $this->info("Geographic Match Rate: {$geoMatchRate}%");

        if ($this->job) {
            $this->info("Job ID: {$this->job->uuid}");
            $this->info("Job Status: {$this->job->status->value}");
        }
    }
}