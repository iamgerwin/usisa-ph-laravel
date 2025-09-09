<?php

namespace App\Services\Scrapers;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class SumbongFloodControlScraperStrategy extends BaseScraperStrategy
{
    private const BASE_URL = 'https://sumbongsapangulo.ph';
    private const FLOOD_PROJECTS_URL = 'https://sumbongsapangulo.ph/flood-control-projects';
    
    /**
     * Scrape flood control projects from SumbongSaPangulo
     */
    public function scrapeProjects(): array
    {
        $projects = [];
        
        try {
            // Try different approaches to get the data
            
            // Approach 1: Try AJAX endpoint used by the website
            $ajaxUrl = 'https://sumbongsapangulo.ph/wp-admin/admin-ajax.php';
            $ajaxProjects = $this->scrapeViaAjax($ajaxUrl);
            if (!empty($ajaxProjects)) {
                return $ajaxProjects;
            }
            
            // Approach 2: Try direct API endpoints
            $apiEndpoints = [
                '/api/flood-projects',
                '/api/projects/flood-control',
                '/data/flood-control-projects.json',
                '/_next/data/buildId/flood-control-projects.json',
            ];
            
            foreach ($apiEndpoints as $endpoint) {
                $response = $this->tryEndpoint(self::BASE_URL . $endpoint);
                if ($response && $response->successful()) {
                    return $this->parseApiResponse($response->json());
                }
            }
            
            // Approach 3: Try scraping the HTML page with proper headers
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->get(self::BASE_URL);
                
            if ($response->successful()) {
                $projects = $this->parseHtmlResponse($response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('Error scraping flood control projects', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $projects;
    }
    
    /**
     * Scrape projects via AJAX endpoint
     */
    private function scrapeViaAjax(string $ajaxUrl): array
    {
        $allProjects = [];
        $page = 0;
        $perPage = 100;
        $hasMore = true;
        
        while ($hasMore && $page < 50) { // Limit to 50 pages to avoid infinite loops
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->asForm()
                    ->timeout(30)
                    ->post($ajaxUrl, [
                        'action' => 'load_more_projects',
                        'page' => $page,
                        'per_page' => $perPage,
                        'search_itm' => '',
                        'region' => '',
                        'municipality' => '',
                        'type_of_work' => '',
                        'year' => '',
                    ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['html'])) {
                        $pageProjects = $this->parseHtmlResponse($data['html']);
                        $allProjects = array_merge($allProjects, $pageProjects);
                    }
                    
                    $hasMore = isset($data['has_more']) && $data['has_more'];
                    $page++;
                    
                    // Small delay to be respectful to the server
                    usleep(500000); // 0.5 seconds
                } else {
                    break;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch AJAX page ' . $page, ['error' => $e->getMessage()]);
                break;
            }
        }
        
        return $allProjects;
    }
    
    /**
     * Try to fetch data from an endpoint
     */
    private function tryEndpoint(string $url): ?\Illuminate\Http\Client\Response
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->get($url);
                
            if ($response->successful()) {
                return $response;
            }
        } catch (\Exception $e) {
            Log::debug("Failed to fetch from {$url}: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Parse API JSON response
     */
    private function parseApiResponse(array $data): array
    {
        $projects = [];
        
        // Handle different possible JSON structures
        $items = $data['projects'] ?? $data['data'] ?? $data['items'] ?? $data;
        
        foreach ($items as $item) {
            $projects[] = $this->processData($item);
        }
        
        return $projects;
    }
    
    /**
     * Parse HTML response and extract table data
     */
    private function parseHtmlResponse(string $html): array
    {
        $projects = [];
        $crawler = new Crawler($html);
        
        // Look for the projects table with class fcp-table
        $table = $crawler->filter('table.fcp-table')->first();
        
        if ($table->count() > 0) {
            $rows = $table->filter('tbody#projects-body tr');
            
            $rows->each(function (Crawler $row) use (&$projects) {
                $cells = $row->filter('td');
                
                if ($cells->count() >= 5) {
                    // Extract project details from table row
                    $descriptionCell = $cells->eq(0);
                    $link = $descriptionCell->filter('a.load-project-card')->first();
                    $description = $link->count() > 0 ? trim($link->text()) : trim($descriptionCell->text());
                    $projectId = $link->count() > 0 ? $link->attr('data-id') : null;
                    
                    // Extract additional data from the report button
                    $reportBtn = $cells->eq(5)->filter('button.open-report-form')->first();
                    $additionalData = [];
                    if ($reportBtn->count() > 0) {
                        $additionalData = [
                            'contract_id' => $reportBtn->attr('data-contract_id'),
                            'region' => $reportBtn->attr('data-region'),
                            'year' => $reportBtn->attr('data-year'),
                        ];
                    }
                    
                    // Extract data from the template if available
                    $templateData = $this->extractTemplateData($crawler, $projectId);
                    
                    $project = array_merge([
                        'project_id' => $projectId,
                        'description' => $description,
                        'location' => trim($cells->eq(1)->text()),
                        'contractor' => trim($cells->eq(2)->text()),
                        'cost' => $this->parseCost(trim($cells->eq(3)->text())),
                        'completion_date' => $this->parseDate(trim($cells->eq(4)->text())),
                    ], $additionalData, $templateData);
                    
                    $projects[] = $this->mapToProjectData($project);
                }
            });
        }
        
        // Also check for JSON-LD or embedded JSON data
        $scriptTags = $crawler->filter('script[type="application/json"], script[type="application/ld+json"]');
        $scriptTags->each(function (Crawler $script) use (&$projects) {
            $content = $script->text();
            $json = json_decode($content, true);
            if ($json && isset($json['projects'])) {
                foreach ($json['projects'] as $item) {
                    $projects[] = $this->processData($item);
                }
            }
        });
        
        // Check for Next.js __NEXT_DATA__
        $nextDataScript = $crawler->filter('script#__NEXT_DATA__')->first();
        if ($nextDataScript->count() > 0) {
            $nextData = json_decode($nextDataScript->text(), true);
            if ($nextData && isset($nextData['props']['pageProps'])) {
                $pageProps = $nextData['props']['pageProps'];
                if (isset($pageProps['projects'])) {
                    foreach ($pageProps['projects'] as $item) {
                        $projects[] = $this->processData($item);
                    }
                }
            }
        }
        
        return $projects;
    }
    
    /**
     * Extract additional data from template elements
     */
    private function extractTemplateData(Crawler $crawler, ?string $projectId): array
    {
        if (!$projectId) {
            return [];
        }
        
        $template = $crawler->filter('#proj-card-' . $projectId)->first();
        if ($template->count() === 0) {
            return [];
        }
        
        $templateData = [];
        
        // Extract start date
        $startDate = $template->filter('.start-date span')->first();
        if ($startDate->count() > 0) {
            $templateData['start_date'] = $this->parseDate(trim($startDate->text()));
        }
        
        // Extract coordinates from location
        $location = $template->filter('.longi span')->first();
        if ($location->count() > 0) {
            $locationText = trim($location->text());
            if (preg_match('/\(([\-\d.]+)\s*,\s*([\-\d.]+)\)/', $locationText, $matches)) {
                $templateData['latitude'] = (float) $matches[1];
                $templateData['longitude'] = (float) $matches[2];
            }
        }
        
        // Extract type of work
        $otherDetails = $template->filter('.others span');
        if ($otherDetails->count() >= 2) {
            $templateData['type_of_work'] = trim($otherDetails->eq(1)->text());
        }
        
        return $templateData;
    }
    
    /**
     * Map scraped data to project format
     */
    private function mapToProjectData(array $data): array
    {
        // Generate a unique ID - use project_id if available, otherwise create from description
        $externalId = $data['project_id'] ?? md5($data['description'] . '|' . $data['location']);
        
        // Parse location to extract province/city information
        $locationData = $this->parseLocation($data['location']);
        
        return [
            'external_id' => $externalId,
            'external_source' => 'sumbongsapangulo',
            'project_name' => $data['description'],
            'description' => $data['description'],
            'project_type' => $data['type_of_work'] ?? 'Flood Control',
            'province_name' => $locationData['province_name'] ?? null,
            'region_name' => $data['region'] ?? $locationData['region_name'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'contractor_name' => $data['contractor'],
            'cost' => $data['cost'],
            'contract_completion_date' => $data['completion_date'],
            'actual_date_started' => $data['start_date'] ?? null,
            'status' => $this->determineStatus($data['completion_date']),
            'data_source' => 'sumbongsapangulo',
            'last_synced_at' => now(),
            'metadata' => [
                'source' => 'sumbongsapangulo_flood_control',
                'project_category' => 'Flood Control',
                'contract_id' => $data['contract_id'] ?? null,
                'funding_year' => $data['year'] ?? null,
                'type_of_work' => $data['type_of_work'] ?? null,
                'original_data' => $data,
            ],
        ];
    }
    
    /**
     * Process raw API data into project model format
     */
    public function processData(array $rawData): array
    {
        return [
            'external_id' => $rawData['id'] ?? md5(json_encode($rawData)),
            'external_source' => 'sumbongsapangulo',
            'project_name' => $rawData['project_description'] ?? $rawData['description'] ?? $rawData['title'] ?? '',
            'description' => $rawData['project_description'] ?? $rawData['description'] ?? '',
            'location' => $this->parseLocation($rawData['location'] ?? ''),
            'contractor_name' => $rawData['contractor'] ?? '',
            'cost' => $this->parseCost($rawData['cost'] ?? $rawData['project_cost'] ?? 0),
            'contract_completion_date' => $this->parseDate($rawData['completion_date'] ?? $rawData['target_completion'] ?? null),
            'status' => $this->determineStatus($rawData['completion_date'] ?? null),
            'project_type' => 'Flood Control',
            'data_source' => 'sumbongsapangulo',
            'last_synced_at' => now(),
            'metadata' => [
                'source' => 'sumbongsapangulo_flood_control',
                'project_category' => 'Flood Control',
                'original_data' => $rawData,
            ],
        ];
    }
    
    /**
     * Parse location string to extract province/city information
     */
    private function parseLocation(string $location): array
    {
        $location = strtoupper(trim($location));
        
        // Common patterns: "AGUSAN DEL NORTE", "BATAAN", "COTABATO (NORTH COTABATO)"
        $parts = preg_split('/[\(\)]/', $location);
        $primary = trim($parts[0]);
        $secondary = isset($parts[1]) ? trim($parts[1]) : null;
        
        return [
            'province_name' => $primary,
            'region_name' => $secondary,
            'full_location' => $location,
        ];
    }
    
    /**
     * Parse cost string to numeric value
     */
    private function parseCost($value): ?float
    {
        if (!$value) {
            return null;
        }
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remove currency symbols and formatting
        $value = preg_replace('/[^\d.,]/', '', $value);
        $value = str_replace(',', '', $value);
        
        return $value ? (float) $value : null;
    }
    
    /**
     * Parse date in MM/DD/YYYY format
     */
    private function parseDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        
        try {
            // Handle MM/DD/YYYY format
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d');
            }
            
            // Try other formats
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date in flood control scraper', [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Determine project status based on completion date
     */
    private function determineStatus(?string $completionDate): string
    {
        if (!$completionDate) {
            return 'ongoing';
        }
        
        try {
            $date = Carbon::parse($completionDate);
            
            if ($date->isPast()) {
                return 'completed';
            } elseif ($date->isFuture()) {
                return 'ongoing';
            }
        } catch (\Exception $e) {
            // Default to ongoing if we can't parse the date
        }
        
        return 'ongoing';
    }
    
    /**
     * Get headers for HTTP requests
     */
    private function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,application/json,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Cache-Control' => 'max-age=0',
        ];
    }
    
    /**
     * Validate scraped data
     */
    public function validateData(array $data): bool
    {
        return !empty($data['project_name']) && !empty($data['external_id']);
    }
    
    /**
     * Get the model class
     */
    public function getModelClass(): string
    {
        return Project::class;
    }
    
    /**
     * Get unique field for duplicate detection
     */
    public function getUniqueField(): string
    {
        return 'external_id';
    }
}