<?php

namespace App\Jobs;

use App\Services\Scrapers\PSGC\ProvinceScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapePSGCProvinces implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting PSGC provinces scraping');

        try {
            $scraper = new ProvinceScraper();
            $scraper->scrape();

            Log::info('PSGC provinces scraping completed');
        } catch (\Exception $e) {
            Log::error('PSGC provinces scraping failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return 'psgc-scraper-provinces';
    }
}