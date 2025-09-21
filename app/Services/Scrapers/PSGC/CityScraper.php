<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\City;
use App\Models\Province;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CityScraper extends BasePSGCScraper
{
    protected function initializeSource(): void
    {
        $this->source = $this->getOrCreateSource(
            'psgc_cities',
            'PSA PSGC Cities',
            'https://psa.gov.ph/classification/psgc/cities'
        );
    }

    public function getEntityType(): string
    {
        return 'cities';
    }

    protected function parseData(Crawler $crawler): array
    {
        $data = [];

        // Find the table containing city data
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
                $cityClass = null;
                $incomeClass = null;

                if ($cells->count() >= 4) {
                    $cityClass = $this->extractCellText($cells->eq(3));
                }
                if ($cells->count() >= 5) {
                    $incomeClass = $this->extractCellText($cells->eq(4));
                }

                // Clean city name (remove "City of" prefix if present)
                $cleanName = preg_replace('/^City of\s+/i', '', $name);

                $data[] = [
                    'psa_code' => $this->parsePSGCCode($code),
                    'name' => $this->cleanText($cleanName),
                    'full_name' => $this->cleanText($name),
                    'province_code' => $this->parsePSGCCode($provinceCode),
                    'city_class' => $cityClass ? $this->cleanText($cityClass) : null,
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
                    // Some cities might be independent (not under a province)
                    // Try to find a special "Independent Cities" province or create one
                    if ($item['province_code'] === '00' || empty($item['province_code'])) {
                        $province = Province::firstOrCreate(
                            ['code' => '00'],
                            [
                                'name' => 'Independent Cities',
                                'region_id' => 1, // NCR or appropriate region
                                'is_active' => true,
                            ]
                        );
                    } else {
                        $this->logRecordError(
                            $item['psa_code'],
                            "Province not found: {$item['province_code']}"
                        );
                        $errors++;
                        continue;
                    }
                }

                $city = City::where('code', $item['psa_code'])
                    ->orWhere('psa_code', $item['psa_code'])
                    ->first();

                $cityData = [
                    'psa_slug' => Str::slug($item['name']),
                    'psa_code' => $item['psa_code'],
                    'psa_name' => $item['full_name'],
                    'city_class' => $item['city_class'],
                    'income_class' => $item['income_class'],
                    'type' => 'city',
                    'psa_synced_at' => now(),
                ];

                if ($city) {
                    // Update existing city
                    $city->update($cityData);
                    $updated++;

                    Log::info("Updated city: {$item['name']} ({$item['psa_code']})");
                } else {
                    // Create new city
                    City::create(array_merge($cityData, [
                        'province_id' => $province->id,
                        'code' => $item['psa_code'],
                        'name' => $item['name'],
                        'is_active' => true,
                        'sort_order' => $processed,
                    ]));
                    $created++;

                    Log::info("Created city: {$item['name']} ({$item['psa_code']})");
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

        Log::info("City scraping completed: Created: $created, Updated: $updated, Errors: $errors");
    }
}