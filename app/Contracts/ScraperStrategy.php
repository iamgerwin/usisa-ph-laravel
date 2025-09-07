<?php

namespace App\Contracts;

use App\Models\ScraperJob;
use Illuminate\Support\Collection;

interface ScraperStrategy
{
    /**
     * Scrape a single item by ID
     */
    public function scrapeItem(int $id): ?array;

    /**
     * Scrape multiple items in a batch
     */
    public function scrapeBatch(array $ids): Collection;

    /**
     * Process scraped data into model-ready format
     */
    public function processData(array $rawData): array;

    /**
     * Validate scraped data
     */
    public function validateData(array $data): bool;

    /**
     * Get the model class for this scraper
     */
    public function getModelClass(): string;

    /**
     * Get unique identifier field for duplicate detection
     */
    public function getUniqueField(): string;

    /**
     * Handle rate limiting
     */
    public function handleRateLimit(): void;

    /**
     * Set the current job for progress tracking
     */
    public function setJob(ScraperJob $job): void;

    /**
     * Get HTTP client configuration
     */
    public function getHttpConfig(): array;
}