<?php

namespace App\Console\Commands;

use App\Services\Scrapers\SumbongFloodControlScraperStrategy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeFloodControlProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:flood-control 
                            {--test : Run in test mode without saving to database}
                            {--limit=10 : Limit the number of projects to scrape}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape flood control projects from SumbongSaPangulo.ph';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting flood control projects scraper...');
        
        try {
            // Get or create the scraper source
            $source = \App\Models\ScraperSource::firstOrCreate(
                ['code' => 'sumbong_flood_control'],
                [
                    'name' => 'SumbongSaPangulo Flood Control Projects',
                    'base_url' => 'https://sumbongsapangulo.ph',
                    'endpoint_pattern' => '/flood-control-projects',
                    'is_active' => true,
                    'rate_limit' => 5,
                    'timeout' => 30,
                    'retry_attempts' => 3,
                    'headers' => [
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,application/json,*/*;q=0.8',
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    ],
                    'scraper_class' => 'App\\Services\\Scrapers\\SumbongFloodControlScraperStrategy',
                    'version' => '1.0.0',
                ]
            );
            
            $scraper = new SumbongFloodControlScraperStrategy($source);
            $testMode = $this->option('test');
            $limit = (int) $this->option('limit');
            
            $this->info('Fetching flood control projects...');
            $projects = $scraper->scrapeProjects();
            
            if (empty($projects)) {
                $this->warn('No projects found. The website might be blocking requests or the structure has changed.');
                
                // Try alternative approach with sample data for testing
                if ($testMode) {
                    $this->info('Using sample data based on the screenshot for testing...');
                    $projects = $this->getSampleProjects();
                }
            }
            
            $projectCount = count($projects);
            $this->info("Found {$projectCount} projects");
            
            if ($limit && $projectCount > $limit) {
                $projects = array_slice($projects, 0, $limit);
                $this->info("Limited to {$limit} projects");
            }
            
            if ($testMode) {
                $this->info('Test mode - displaying projects without saving:');
                $this->displayProjects($projects);
            } else {
                $this->saveProjects($projects);
            }
            
            $this->info('Scraping completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error during scraping: ' . $e->getMessage());
            Log::error('Flood control scraper error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Display projects in a table format
     */
    private function displayProjects(array $projects): void
    {
        $headers = ['Project Name', 'Location', 'Contractor', 'Cost', 'Completion Date'];
        $rows = [];
        
        foreach ($projects as $project) {
            $location = is_array($project['location']) 
                ? ($project['location']['full_location'] ?? $project['location']['province_name'] ?? 'N/A')
                : $project['location'] ?? 'N/A';
                
            $rows[] = [
                substr($project['project_name'] ?? 'N/A', 0, 50),
                $location,
                $project['contractor_name'] ?? 'N/A',
                number_format($project['cost'] ?? 0, 2),
                $project['contract_completion_date'] ?? 'N/A',
            ];
        }
        
        $this->table($headers, $rows);
    }
    
    /**
     * Save projects to database
     */
    private function saveProjects(array $projects): void
    {
        $saved = 0;
        $updated = 0;
        $errors = 0;
        
        foreach ($projects as $projectData) {
            try {
                $model = \App\Models\Project::updateOrCreate(
                    [
                        'external_id' => $projectData['external_id'],
                        'external_source' => 'sumbongsapangulo',
                    ],
                    $projectData
                );
                
                if ($model->wasRecentlyCreated) {
                    $saved++;
                } else {
                    $updated++;
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to save project: " . $e->getMessage());
            }
        }
        
        $this->info("Results: {$saved} new, {$updated} updated, {$errors} errors");
    }
    
    /**
     * Get sample projects based on the screenshot
     */
    private function getSampleProjects(): array
    {
        return [
            [
                'external_id' => md5('flood-1'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of Flood Mitigation Structure along Agusan River (Dankias Section) Package 1, Butuan City',
                'description' => 'Construction of Flood Mitigation Structure along Agusan River (Dankias Section) Package 1, Butuan City',
                'location' => [
                    'province_name' => 'AGUSAN DEL NORTE',
                    'full_location' => 'AGUSAN DEL NORTE',
                ],
                'contractor_name' => 'ME 3 CONSTRUCTION',
                'cost' => 137272357.59,
                'contract_completion_date' => '2025-05-30',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
            [
                'external_id' => md5('flood-2'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of Bank Protection, Lower Agusan River, Barangay Golden Ribbon, Butuan City',
                'description' => 'Construction of Bank Protection, Lower Agusan River, Barangay Golden Ribbon, Butuan City',
                'location' => [
                    'province_name' => 'AGUSAN DEL NORTE',
                    'full_location' => 'AGUSAN DEL NORTE',
                ],
                'contractor_name' => 'RAMISES CONSTRUCTION',
                'cost' => 96158174.50,
                'contract_completion_date' => '2025-05-30',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
            [
                'external_id' => md5('flood-3'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of Flood Mitigation Structure along Orani River, Orani, Bataan',
                'description' => 'Construction of Flood Mitigation Structure along Orani River, Orani, Bataan',
                'location' => [
                    'province_name' => 'BATAAN',
                    'full_location' => 'BATAAN',
                ],
                'contractor_name' => 'ORANI CONSTRUCTION AND SUPPLY CORPORATION (FORMERLY:ORANI BUILDERS & SUPPLY)',
                'cost' => 48999998.54,
                'contract_completion_date' => '2025-05-28',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
            [
                'external_id' => md5('flood-4'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of Flood Control Dike along Kabacan River Sta. 48 + 620 - Sta. 48 + 944 L/S, Barangay Doles, Magpet, Cotabato',
                'description' => 'Construction of Flood Control Dike along Kabacan River Sta. 48 + 620 - Sta. 48 + 944 L/S, Barangay Doles, Magpet, Cotabato',
                'location' => [
                    'province_name' => 'COTABATO',
                    'region_name' => 'NORTH COTABATO',
                    'full_location' => 'COTABATO (NORTH COTABATO)',
                ],
                'contractor_name' => 'MAEP SUMMIT KONSTRUKT CO.',
                'cost' => 96017492.67,
                'contract_completion_date' => '2025-05-27',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
            [
                'external_id' => md5('flood-5'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of Drainage Structure, Barangay Sacsac, Consolacion, Cebu',
                'description' => 'Construction of Drainage Structure, Barangay Sacsac, Consolacion, Cebu',
                'location' => [
                    'province_name' => 'CEBU',
                    'full_location' => 'CEBU',
                ],
                'contractor_name' => 'XLA CONSTRUCTION',
                'cost' => 4939190.15,
                'contract_completion_date' => '2025-05-27',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
            [
                'external_id' => md5('flood-6'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of River Control Structure, Bucayao River, Managpi Section, Calapan City, Oriental Mindoro',
                'description' => 'Construction of River Control Structure, Bucayao River, Managpi Section, Calapan City, Oriental Mindoro',
                'location' => [
                    'province_name' => 'ORIENTAL MINDORO',
                    'full_location' => 'ORIENTAL MINDORO',
                ],
                'contractor_name' => 'CEFF TRADING & ENGINEERING SERVICES',
                'cost' => 19299949.90,
                'contract_completion_date' => '2025-05-27',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
            [
                'external_id' => md5('flood-7'),
                'external_source' => 'sumbongsapangulo',
                'project_name' => 'Construction of Drainage System, Mag-Asawang Tubig RIS, Naujan, Oriental Mindoro',
                'description' => 'Construction of Drainage System, Mag-Asawang Tubig RIS, Naujan, Oriental Mindoro',
                'location' => [
                    'province_name' => 'ORIENTAL MINDORO',
                    'full_location' => 'ORIENTAL MINDORO',
                ],
                'contractor_name' => 'CEFF TRADING & ENGINEERING SERVICES / JUWAWI CORPORATION',
                'cost' => 14699999.28,
                'contract_completion_date' => '2025-05-26',
                'status' => 'ongoing',
                'project_type' => 'Flood Control',
                'data_source' => 'sumbongsapangulo',
                'last_synced_at' => now(),
            ],
        ];
    }
}