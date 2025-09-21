<?php

namespace App\Console\Commands;

use App\Models\ScraperSource;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScheduleDimeScraperCommand extends Command
{
    protected $signature = 'dime:schedule
                            {--immediate : Start scraping immediately if online}
                            {--force : Force scraping even if recently run}';

    protected $description = 'Smart scheduler for DIME scraping that checks availability';

    public function handle()
    {
        $this->info('DIME Smart Scraper Scheduler');
        $this->newLine();

        // First check if DIME is online
        $isOnline = $this->checkDimeAvailability();

        if (!$isOnline) {
            $this->warn('DIME is currently offline/under maintenance.');
            $this->info('Scraping will be attempted when the site comes back online.');
            $this->logStatus('offline', 'Site is under maintenance');
            return Command::SUCCESS;
        }

        $this->info('✅ DIME is online!');

        // Check if we should run scraping
        if (!$this->shouldRunScraping() && !$this->option('force')) {
            $this->info('Scraping was recently run. Use --force to override.');
            return Command::SUCCESS;
        }

        // Determine how many projects to scrape
        $projectsToScrape = $this->calculateProjectsToScrape();

        $this->info("Planning to scrape {$projectsToScrape} projects...");

        if ($this->option('immediate') || $this->confirm('Start scraping now?')) {
            $this->startIncrementalScraping($projectsToScrape);
        }

        return Command::SUCCESS;
    }

    protected function checkDimeAvailability(): bool
    {
        $this->info('Checking DIME availability...');

        // Run status check command
        $exitCode = $this->callSilently('dime:check-status');

        // Check cached status
        $mainStatus = Cache::get('dime_status_Main Site');
        $apiStatus = Cache::get('dime_status_API Projects');

        return ($mainStatus && $mainStatus['online']) ||
               ($apiStatus && $apiStatus['online']);
    }

    protected function shouldRunScraping(): bool
    {
        // Check last successful scrape
        $lastScrape = Cache::get('dime_last_successful_scrape');

        if (!$lastScrape) {
            return true; // Never scraped before
        }

        $hoursSinceLastScrape = now()->diffInHours($lastScrape);

        // Run every 6 hours at most
        return $hoursSinceLastScrape >= 6;
    }

    protected function calculateProjectsToScrape(): int
    {
        // Get current project count
        $currentCount = Project::where('external_source', 'dime')->count();

        // Start with smaller batches, increase over time
        if ($currentCount == 0) {
            return 1000; // First run - start small
        } elseif ($currentCount < 5000) {
            return 2000; // Ramp up
        } elseif ($currentCount < 10000) {
            return 5000; // Larger batch
        } else {
            return 20000; // Full scrape
        }
    }

    protected function startIncrementalScraping(int $limit): void
    {
        $this->info('Starting incremental scraping...');

        // Get the highest offset we've scraped
        $lastOffset = $this->getLastOffset();

        $this->info("Starting from offset: {$lastOffset}");

        // Run scraper with incremental strategy
        $exitCode = $this->call('dime:scrape-projects', [
            '--limit' => $limit,
            '--offset' => $lastOffset,
            '--chunk' => 100,
            '--delay' => 1,
            '--retry' => 3,
        ]);

        if ($exitCode === Command::SUCCESS) {
            Cache::put('dime_last_successful_scrape', now(), 86400);
            Cache::put('dime_last_offset', $lastOffset + $limit, 86400);
            $this->logStatus('success', "Scraped {$limit} projects starting from offset {$lastOffset}");
        } else {
            $this->logStatus('failed', 'Scraping failed');
        }
    }

    protected function getLastOffset(): int
    {
        // Try to get from cache first
        $cachedOffset = Cache::get('dime_last_offset', 0);

        // Verify against database
        $maxExternalId = Project::where('external_source', 'dime')
            ->max('external_id');

        return max($cachedOffset, (int) $maxExternalId);
    }

    protected function logStatus(string $status, string $message): void
    {
        $logData = [
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'projects_count' => Project::where('external_source', 'dime')->count(),
        ];

        Log::info('DIME Scheduler', $logData);

        // Store in cache for dashboard
        $history = Cache::get('dime_scraper_history', []);
        array_unshift($history, $logData);
        $history = array_slice($history, 0, 50); // Keep last 50 entries
        Cache::put('dime_scraper_history', $history, 86400);
    }
}