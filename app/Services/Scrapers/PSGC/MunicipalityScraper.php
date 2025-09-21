<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\City;
use App\Models\Province;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class MunicipalityScraper extends BasePSGCScraper
{
    protected function initializeSource(): void
    {
        $this->source = $this->getOrCreateSource(
            'psgc_municipalities',
            'PSA PSGC Municipalities',
            'https://psa.gov.ph/classification/psgc/municipalities'
        );
    }

    public function getEntityType(): string
    {
        return 'municipalities';
    }

    protected function parseData(Crawler $crawler): array
    {
        $data = [];

        // Find the table containing municipality data
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
                $provinceCode = $this->extractCellText($cells->eq(2));

                // Parse additional info if available
                $incomeClass = null;

                if ($cells->count() >= 4) {
                    $incomeClass = $this->extractCellText($cells->eq(3));
                }

                $data[] = [
                    'psa_code' => $this->parsePSGCCode($code),
                    'name' => $this->cleanText($name),
                    'province_code' => $this->parsePSGCCode($provinceCode),
                    'income_class' => $incomeClass ? $this->cleanText($incomeClass) : null,
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
                // Find the province
                $province = Province::where('code', $item['province_code'])
                    ->orWhere('psa_code', $item['province_code'])
                    ->first();

                if (!$province) {
                    $this->logRecordError(
                        $item['psa_code'],
                        "Province not found: {$item['province_code']}"
                    );
                    $errors++;
                    continue;
                }

                // Check if municipality already exists (using City model with type=municipality)
                $municipality = City::where('code', $item['psa_code'])
                    ->orWhere('psa_code', $item['psa_code'])
                    ->first();

                $municipalityData = [
                    'psa_code' => $item['psa_code'],
                    'psa_name' => $item['name'],
                    'income_class' => $item['income_class'],
                    'type' => 'municipality',
                    'psa_synced_at' => now(),
                ];

                if ($municipality) {
                    // Update existing municipality
                    $municipality->update($municipalityData);
                    $updated++;

                    Log::info("Updated municipality: {$item['name']} ({$item['psa_code']})");
                } else {
                    // Create new municipality
                    City::create(array_merge($municipalityData, [
                        'province_id' => $province->id,
                        'code' => $item['psa_code'],
                        'name' => $item['name'],
                        'is_active' => true,
                        'sort_order' => $processed,
                    ]));
                    $created++;

                    Log::info("Created municipality: {$item['name']} ({$item['psa_code']})");
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

        Log::info("Municipality scraping completed: Created: $created, Updated: $updated, Errors: $errors");
    }
}