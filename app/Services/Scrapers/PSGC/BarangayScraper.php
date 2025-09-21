<?php

namespace App\Services\Scrapers\PSGC;

use App\Models\Barangay;
use App\Models\City;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BarangayScraper extends BasePSGCScraper
{
    private int $batchSize = 1000;

    protected function initializeSource(): void
    {
        $this->source = $this->getOrCreateSource(
            'psgc_barangays',
            'PSA PSGC Barangays',
            'https://psa.gov.ph/classification/psgc/barangays'
        );
    }

    public function getEntityType(): string
    {
        return 'barangays';
    }

    protected function parseData(Crawler $crawler): array
    {
        $data = [];

        // Find the table containing barangay data
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
                $cityMunCode = $this->extractCellText($cells->eq(2));

                // Parse additional info if available
                $urbanRural = null;

                if ($cells->count() >= 4) {
                    $classification = $this->extractCellText($cells->eq(3));
                    if (in_array(strtolower($classification), ['urban', 'rural'])) {
                        $urbanRural = strtolower($classification);
                    }
                }

                $data[] = [
                    'psa_code' => $this->parsePSGCCode($code),
                    'name' => $this->cleanText($name),
                    'city_mun_code' => $this->parsePSGCCode($cityMunCode),
                    'urban_rural' => $urbanRural,
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

        // Process in batches for better performance with large datasets
        $chunks = array_chunk($data, $this->batchSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            Log::info("Processing barangay batch " . ($chunkIndex + 1) . " of " . count($chunks));

            DB::beginTransaction();

            try {
                foreach ($chunk as $item) {
                    try {
                        // Find the city/municipality
                        $city = City::where('code', $item['city_mun_code'])
                            ->orWhere('psa_code', $item['city_mun_code'])
                            ->first();

                        if (!$city) {
                            $this->logRecordError(
                                $item['psa_code'],
                                "City/Municipality not found: {$item['city_mun_code']}"
                            );
                            $errors++;
                            continue;
                        }

                        $barangay = Barangay::where('code', $item['psa_code'])
                            ->orWhere('psa_code', $item['psa_code'])
                            ->first();

                        $barangayData = [
                            'psa_slug' => Str::slug($item['name']),
                            'psa_code' => $item['psa_code'],
                            'psa_name' => $item['name'],
                            'urban_rural' => $item['urban_rural'],
                            'psa_synced_at' => now(),
                        ];

                        if ($barangay) {
                            // Update existing barangay
                            $barangay->update($barangayData);
                            $updated++;
                        } else {
                            // Create new barangay
                            Barangay::create(array_merge($barangayData, [
                                'city_id' => $city->id,
                                'code' => $item['psa_code'],
                                'name' => $item['name'],
                                'is_active' => true,
                                'sort_order' => $processed,
                            ]));
                            $created++;
                        }

                        $processed++;
                        $this->updateProgress($processed, count($data));

                        if ($this->job) {
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

                DB::commit();

                // Update job statistics after each batch
                if ($this->job) {
                    $this->job->update([
                        'create_count' => $created,
                        'update_count' => $updated,
                        'error_count' => $errors,
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Batch processing failed: " . $e->getMessage());
                throw $e;
            }
        }

        Log::info("Barangay scraping completed: Created: $created, Updated: $updated, Errors: $errors");
    }
}