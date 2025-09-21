<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\Province;
use App\Models\Region;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class ProvinceScraper extends BasePSGCScraper
{
    protected function initializeSource(): void
    {
        $this->source = $this->getOrCreateSource(
            'psgc_provinces',
            'PSA PSGC Provinces',
            'https://psa.gov.ph/classification/psgc/provinces'
        );
    }

    public function getEntityType(): string
    {
        return 'provinces';
    }

    protected function parseData(Crawler $crawler): array
    {
        $data = [];

        // Find the table containing province data
        $table = $crawler->filter('table')->first();

        if ($table->count() === 0) {
            throw new \Exception("No table found on the page");
        }

        // Parse table rows (skip header)
        $table->filter('tbody tr')->each(function (Crawler $row) use (&$data) {
            $cells = $row->filter('td');

            if ($cells->count() >= 3) {
                $code = $this->extractCellText($cells->eq(0));
                $name = $this->extractCellText($cells->eq(1));
                $regionCode = $this->extractCellText($cells->eq(2));

                // Parse additional info if available
                $incomeClass = null;
                $oldName = null;

                if ($cells->count() >= 4) {
                    $incomeClass = $this->extractCellText($cells->eq(3));
                }

                // Check if name contains old name in parentheses
                if (preg_match('/(.+?)\s*\(formerly (.+?)\)/', $name, $matches)) {
                    $name = trim($matches[1]);
                    $oldName = trim($matches[2]);
                }

                $data[] = [
                    'psa_code' => $this->parsePSGCCode($code),
                    'name' => $this->cleanText($name),
                    'region_code' => $this->parsePSGCCode($regionCode),
                    'income_class' => $incomeClass ? $this->cleanText($incomeClass) : null,
                    'old_name' => $oldName,
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
                // Find the region
                $region = Region::where('code', $item['region_code'])
                    ->orWhere('psa_code', $item['region_code'])
                    ->first();

                if (!$region) {
                    $this->logRecordError(
                        $item['psa_code'],
                        "Region not found: {$item['region_code']}"
                    );
                    $errors++;
                    continue;
                }

                $province = Province::where('code', $item['psa_code'])
                    ->orWhere('psa_code', $item['psa_code'])
                    ->first();

                $provinceData = [
                    'psa_code' => $item['psa_code'],
                    'psa_name' => $item['name'],
                    'income_class' => $item['income_class'],
                    'psa_synced_at' => now(),
                ];

                if ($item['old_name']) {
                    $provinceData['psa_data'] = ['old_name' => $item['old_name']];
                }

                if ($province) {
                    // Update existing province
                    $province->update($provinceData);
                    $updated++;

                    Log::info("Updated province: {$item['name']} ({$item['psa_code']})");
                } else {
                    // Create new province
                    Province::create(array_merge($provinceData, [
                        'region_id' => $region->id,
                        'code' => $item['psa_code'],
                        'name' => $item['name'],
                        'is_active' => true,
                        'sort_order' => $processed,
                    ]));
                    $created++;

                    Log::info("Created province: {$item['name']} ({$item['psa_code']})");
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

        Log::info("Province scraping completed: Created: $created, Updated: $updated, Errors: $errors");
    }
}