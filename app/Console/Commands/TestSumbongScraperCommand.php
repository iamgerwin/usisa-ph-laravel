<?php

namespace App\Console\Commands;

use App\Services\Scrapers\SumbongSaPanguloScraperStrategy;
use Illuminate\Console\Command;

class TestSumbongScraperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sumbong-scraper {--data=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Sumbong Sa Pangulo scraper strategy with sample data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Sumbong Sa Pangulo Scraper Strategy...');
        
        // Sample test data with both PascalCase and camelCase variations
        $testData = $this->option('data') ? json_decode($this->option('data'), true) : [
            'Id' => 12345,
            'ProjectName' => 'Construction of Multi-Purpose Building',
            'ProjectCode' => 'MPB-2025-001',
            'Description' => 'Construction of a 3-story multi-purpose building for community use',
            'ProjectImageUrl' => 'https://example.com/image.jpg',
            'StreetAddress' => '123 Main Street',
            'City' => 'Quezon City',
            'CityCode' => 'QC',
            'ZipCode' => '1100',
            'Barangay' => 'Barangay Commonwealth',
            'BarangayCode' => 'BC',
            'Province' => 'Metro Manila',
            'ProvinceCode' => 'MM',
            'Region' => 'National Capital Region',
            'RegionCode' => 'NCR',
            'Country' => 'Philippines',
            'Latitude' => 14.6760,
            'Longitude' => 121.0437,
            'Status' => 'Ongoing',
            'Cost' => 5000000.00,
            'UtilizedAmount' => 2500000.00,
            'DateStarted' => '2025-01-15',
            'ActualDateStarted' => '2025-01-20',
            'ContractCompletionDate' => '2025-12-31',
            'PhysicalProgress' => 45.5,
            'ContractorName' => 'ABC Construction Corp.',
            'ProjectLocation' => 'Quezon City, Metro Manila',
            'UpdatesCount' => 5,
            'ImplementingOffices' => [
                [
                    'Id' => 1,
                    'Name' => 'Department of Public Works and Highways',
                    'NameAbbreviation' => 'DPWH',
                    'LogoUrl' => 'https://example.com/dpwh-logo.png'
                ]
            ],
            'Contractors' => [
                [
                    'Id' => 101,
                    'Name' => 'ABC Construction Corp.',
                    'NameAbbreviation' => 'ABC',
                ]
            ],
            'SourceOfFunds' => [
                [
                    'Id' => 201,
                    'Name' => 'General Appropriations Act',
                    'NameAbbreviation' => 'GAA',
                ]
            ],
            'Program' => [
                'Id' => 301,
                'ProgramName' => 'Build Build Build',
                'NameAbbreviation' => 'BBB',
                'ProgramDescription' => 'Infrastructure development program'
            ]
        ];
        
        $scraper = new SumbongSaPanguloScraperStrategy();
        
        // Test validation
        $this->info("\n📋 Testing data validation...");
        $isValid = $scraper->validateData($testData);
        if ($isValid) {
            $this->info("✅ Data validation passed");
        } else {
            $this->error("❌ Data validation failed");
            return Command::FAILURE;
        }
        
        // Test data processing
        $this->info("\n🔄 Testing data processing...");
        try {
            $processedData = $scraper->processData($testData);
            
            $this->info("✅ Data processed successfully!");
            $this->info("\n📊 Processed Data Summary:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['External ID', $processedData['external_id'] ?? 'N/A'],
                    ['Data Source', $processedData['data_source'] ?? 'N/A'],
                    ['Project Name', $processedData['project_name'] ?? 'N/A'],
                    ['Project Code', $processedData['project_code'] ?? 'N/A'],
                    ['Status', $processedData['status'] ?? 'N/A'],
                    ['Cost', number_format($processedData['cost'] ?? 0, 2)],
                    ['City', $processedData['city_name'] ?? 'N/A'],
                    ['Province', $processedData['province_name'] ?? 'N/A'],
                    ['Region', $processedData['region_name'] ?? 'N/A'],
                    ['Date Started', $processedData['date_started'] ?? 'N/A'],
                    ['Data Source', $processedData['data_source'] ?? 'N/A'],
                ]
            );
            
            // Test with camelCase variation
            $this->info("\n🔄 Testing with camelCase fields...");
            $camelCaseData = [
                'id' => 67890,
                'projectName' => 'Road Widening Project',
                'status' => 'completed',
                'cost' => 3000000,
                'dateStarted' => '2024-06-01',
            ];
            
            if ($scraper->validateData($camelCaseData)) {
                $camelProcessed = $scraper->processData($camelCaseData);
                $this->info("✅ camelCase processing successful!");
                $this->info("Project: " . ($camelProcessed['project_name'] ?? 'N/A'));
                $this->info("Status: " . ($camelProcessed['status'] ?? 'N/A'));
            }
            
            // Test Next.js structure
            $this->info("\n🔄 Testing Next.js response structure...");
            $nextJsData = [
                'pageProps' => [
                    'project' => $testData
                ]
            ];
            
            if ($scraper->validateData($nextJsData)) {
                $this->info("✅ Next.js structure validation passed");
            }
            
            $this->info("\n✅ All tests completed successfully!");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error processing data: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}