<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\ScraperJob;
use App\Models\ScraperSource;
use App\Enums\ScraperJobStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PSGCScraperOrchestrator
{
    private array $scrapers = [
        'regions' => RegionScraper::class,
        'provinces' => ProvinceScraper::class,
        'cities' => CityScraper::class,
        'municipalities' => MunicipalityScraper::class,
        'barangays' => BarangayScraper::class,
    ];

    /**
     * Run all PSGC scrapers in the correct order
     */
    public function runAll(): array
    {
        $results = [];

        Log::info("Starting PSGC full scraping process");

        foreach ($this->scrapers as $type => $scraperClass) {
            try {
                Log::info("Starting {$type} scraper");
                $result = $this->runScraper($scraperClass, $type);
                $results[$type] = $result;
                Log::info("Completed {$type} scraper", $result);
            } catch (\Exception $e) {
                Log::error("Failed to run {$type} scraper: " . $e->getMessage());
                $results[$type] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("PSGC scraping process completed", $results);

        return $results;
    }

    /**
     * Run a specific scraper type
     */
    public function runSpecific(string $type): array
    {
        if (!isset($this->scrapers[$type])) {
            throw new \InvalidArgumentException("Invalid scraper type: {$type}");
        }

        $scraperClass = $this->scrapers[$type];
        return $this->runScraper($scraperClass, $type);
    }

    /**
     * Run multiple specific scrapers
     */
    public function runMultiple(array $types): array
    {
        $results = [];

        foreach ($types as $type) {
            try {
                $results[$type] = $this->runSpecific($type);
            } catch (\Exception $e) {
                Log::error("Failed to run {$type} scraper: " . $e->getMessage());
                $results[$type] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Run a single scraper
     */
    private function runScraper(string $scraperClass, string $type): array
    {
        $startTime = microtime(true);

        // Create scraper job record
        $job = $this->createScraperJob($type);

        try {
            // Instantiate and run scraper
            $scraper = new $scraperClass();
            $scraper->scrape($job);

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'status' => 'completed',
                'job_id' => $job->uuid,
                'duration' => $duration,
                'stats' => [
                    'created' => $job->create_count,
                    'updated' => $job->update_count,
                    'errors' => $job->error_count,
                ],
            ];
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);

            return [
                'status' => 'failed',
                'job_id' => $job->uuid,
                'duration' => $duration,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a scraper job record
     */
    private function createScraperJob(string $type): ScraperJob
    {
        // Get or create the source
        $source = ScraperSource::firstOrCreate(
            ['code' => "psgc_{$type}"],
            [
                'name' => "PSA PSGC " . ucfirst($type),
                'base_url' => "https://psa.gov.ph/classification/psgc/{$type}",
                'is_active' => true,
                'scraper_class' => $this->scrapers[$type],
                'version' => '1.0',
            ]
        );

        return ScraperJob::create([
            'source_id' => $source->id,
            'start_id' => 0,  // Web scraping doesn't use ID ranges
            'end_id' => 0,    // Web scraping doesn't use ID ranges
            'current_id' => 0,
            'chunk_size' => 1000, // For batch processing
            'status' => ScraperJobStatus::PENDING,
            'triggered_by' => 'orchestrator',
            'notes' => "PSGC {$type} scraping job",
        ]);
    }

    /**
     * Get scraper statistics
     */
    public function getStatistics(): array
    {
        $stats = [];

        foreach ($this->scrapers as $type => $scraperClass) {
            $source = ScraperSource::where('code', "psgc_{$type}")->first();

            if ($source) {
                $stats[$type] = $source->getStatistics();
                $stats[$type]['last_run'] = $source->latestJob?->completed_at;
            } else {
                $stats[$type] = [
                    'total_jobs' => 0,
                    'last_run' => null,
                ];
            }
        }

        return $stats;
    }

    /**
     * Check if any scraper is currently running
     */
    public function hasRunningJobs(): bool
    {
        return ScraperJob::running()
            ->whereHas('source', function ($query) {
                $query->where('code', 'like', 'psgc_%');
            })
            ->exists();
    }

    /**
     * Get available scraper types
     */
    public function getAvailableTypes(): array
    {
        return array_keys($this->scrapers);
    }
}