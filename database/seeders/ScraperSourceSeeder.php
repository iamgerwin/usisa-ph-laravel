<?php

namespace Database\Seeders;

use App\Models\ScraperSource;
use Illuminate\Database\Seeder;

class ScraperSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ScraperSource::create([
            'code' => 'dime',
            'name' => 'DIME.gov.ph',
            'base_url' => 'https://api.dime.gov.ph',
            'endpoint_pattern' => '/api/v1/projects/{id}',
            'is_active' => true,
            'rate_limit' => 10,
            'timeout' => 30,
            'retry_attempts' => 3,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'USISA-Scraper/1.0',
            ],
            'field_mapping' => [
                'title' => 'project_name',
                'description' => 'project_description',
                'status' => 'project_status',
                'cost' => 'approved_budget',
                'contractor' => 'contractor_name',
                'location' => 'project_location',
                'progress' => 'physical_progress',
            ],
            'metadata' => [
                'rate_limit_delay' => 60,
                'batch_size' => 50,
            ],
            'scraper_class' => 'App\\Services\\Scrapers\\DimeScraperStrategy',
            'version' => '1.0.0',
        ]);

        $this->command->info('DIME scraper source created successfully.');
    }
}