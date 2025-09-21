<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\ScraperJob;
use App\Models\ScraperSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

abstract class BasePSGCScraper
{
    protected ScraperSource $source;
    protected ?ScraperJob $job = null;
    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (compatible; PSGC-Scraper/1.0)',
    ];
    protected int $retryAttempts = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function __construct()
    {
        $this->initializeSource();
    }

    abstract protected function initializeSource(): void;
    abstract protected function parseData(Crawler $crawler): array;
    abstract protected function processData(array $data): void;
    abstract public function getEntityType(): string;

    /**
     * Run the scraper
     */
    public function scrape(?ScraperJob $job = null): void
    {
        $this->job = $job;

        if ($this->job) {
            $this->job->markAsRunning();
        }

        try {
            $html = $this->fetchPage();
            $crawler = new Crawler($html);
            $data = $this->parseData($crawler);

            Log::info("PSGC Scraper: Found " . count($data) . " {$this->getEntityType()} records");

            $this->processData($data);

            if ($this->job) {
                $this->job->markAsCompleted();
            }
        } catch (\Exception $e) {
            Log::error("PSGC Scraper Error: " . $e->getMessage());

            if ($this->job) {
                $this->job->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Fetch page with retry logic
     */
    protected function fetchPage(): string
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryAttempts) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->timeout(30)
                    ->get($this->source->base_url);

                if ($response->successful()) {
                    return $response->body();
                }

                throw new \Exception("HTTP Error: " . $response->status());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000 * $attempts);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Extract text from table cell
     */
    protected function extractCellText(Crawler $cell): string
    {
        return trim($cell->text(''));
    }

    /**
     * Parse PSGC code
     */
    protected function parsePSGCCode(string $code): string
    {
        // Remove any non-numeric characters
        return preg_replace('/[^0-9]/', '', $code);
    }

    /**
     * Clean text data
     */
    protected function cleanText(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        // Remove leading/trailing whitespace
        $text = trim($text);
        // Remove special characters that might cause issues
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        return $text;
    }

    /**
     * Parse correspondence code (for old format compatibility)
     */
    protected function parseCorrespondenceCode(string $text): ?string
    {
        if (preg_match('/\(([^)]+)\)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Remove correspondence code from name
     */
    protected function removeCorrespondenceCode(string $text): string
    {
        return trim(preg_replace('/\([^)]+\)/', '', $text));
    }

    /**
     * Update job progress
     */
    protected function updateProgress(int $processed, int $total): void
    {
        if ($this->job) {
            $this->job->updateProgress($processed);
            $this->job->addStatistic('total_records', $total);
            $this->job->addStatistic('processed_records', $processed);
        }
    }

    /**
     * Log error for specific record
     */
    protected function logRecordError(string $identifier, string $error): void
    {
        Log::error("PSGC Scraper Error for {$identifier}: {$error}");

        if ($this->job) {
            $this->job->logError(0, $error, ['identifier' => $identifier]);
            $this->job->incrementError();
        }
    }

    /**
     * Get or create scraper source
     */
    protected function getOrCreateSource(string $code, string $name, string $url): ScraperSource
    {
        return ScraperSource::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'base_url' => $url,
                'is_active' => true,
                'rate_limit' => 60,
                'timeout' => 30,
                'retry_attempts' => 3,
                'headers' => $this->headers,
                'scraper_class' => get_class($this),
                'version' => '1.0',
            ]
        );
    }
}