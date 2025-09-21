<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\Region;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegionScraper extends BasePSGCScraper
{
    protected function initializeSource(): void
    {
        $this->source = $this->getOrCreateSource(
            'psgc_regions',
            'PSA PSGC Regions',
            'https://psa.gov.ph/classification/psgc/regions'
        );
    }

    public function getEntityType(): string
    {
        return 'regions';
    }

    protected function parseData(Crawler $crawler): array
    {
        $data = [];

        // Find the table containing region data
        $table = $crawler->filter('table')->first();

        if ($table->count() === 0) {
            throw new \Exception("No table found on the page");
        }

        // Parse table rows (skip header)
        $table->filter('tbody tr')->each(function (Crawler $row) use (&$data) {
            $cells = $row->filter('td');

            if ($cells->count() >= 2) {
                $code = $this->extractCellText($cells->eq(0));
                $name = $this->extractCellText($cells->eq(1));

                // Parse additional info if available
                $abbreviation = null;
                if ($cells->count() >= 3) {
                    $abbreviation = $this->extractCellText($cells->eq(2));
                }

                $data[] = [
                    'psa_code' => $this->parsePSGCCode($code),
                    'name' => $this->cleanText($name),
                    'abbreviation' => $abbreviation ? $this->cleanText($abbreviation) : null,
                ];
            }
        });

        return $data;
    }

    protected function processData(array $data): void
    {
        $processed = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($data as $item) {
            try {
                $region = Region::where('code', $item['psa_code'])
                    ->orWhere('psa_code', $item['psa_code'])
                    ->first();

                if ($region) {
                    // Update existing region
                    $region->update([
                        'psa_slug' => Str::slug($item['name']),
                        'psa_code' => $item['psa_code'],
                        'psa_name' => $item['name'],
                        'abbreviation' => $item['abbreviation'] ?? $region->abbreviation,
                        'psa_synced_at' => now(),
                    ]);
                    $updated++;

                    Log::info("Updated region: {$item['name']} ({$item['psa_code']})");
                } else {
                    // Create new region
                    Region::create([
                        'code' => $item['psa_code'],
                        'psa_slug' => Str::slug($item['name']),
                        'psa_code' => $item['psa_code'],
                        'name' => $item['name'],
                        'psa_name' => $item['name'],
                        'abbreviation' => $item['abbreviation'],
                        'is_active' => true,
                        'sort_order' => $processed,
                        'psa_synced_at' => now(),
                    ]);
                    $created++;

                    Log::info("Created region: {$item['name']} ({$item['psa_code']})");
                }

                $processed++;
                $this->updateProgress($processed, count($data));

                if ($this->job) {
                    if ($created > 0) {
                        $this->job->incrementCreate();
                    }
                    if ($updated > 0) {
                        $this->job->incrementUpdate();
                    }
                    $this->job->incrementSuccess();
                }
            } catch (\Exception $e) {
                $errors++;
                $this->logRecordError(
                    $item['psa_code'] ?? 'unknown',
                    $e->getMessage()
                );
            }
        }

        Log::info("Region scraping completed: Created: $created, Updated: $updated, Errors: $errors");
    }
}