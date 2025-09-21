<?php

namespace App\Jobs;

use App\Services\Scrapers\PSGC\PSGCScraperOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapePSGCData implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?array $types;
    public int $timeout = 3600; // 1 hour
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param array|null $types Specific types to scrape, or null for all
     */
    public function __construct(?array $types = null)
    {
        $this->types = $types;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orchestrator = new PSGCScraperOrchestrator();

        Log::info('Starting PSGC data scraping job', [
            'types' => $this->types ?? 'all',
        ]);

        try {
            if ($this->types === null) {
                // Run all scrapers
                $results = $orchestrator->runAll();
            } else {
                // Run specific scrapers
                $results = $orchestrator->runMultiple($this->types);
            }

            Log::info('PSGC data scraping completed', $results);
        } catch (\Exception $e) {
            Log::error('PSGC data scraping failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        if ($this->types === null) {
            return 'psgc-scraper-all';
        }

        return 'psgc-scraper-' . implode('-', $this->types);
    }
}