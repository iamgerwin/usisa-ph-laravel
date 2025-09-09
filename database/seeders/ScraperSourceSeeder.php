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
        // Create manual source for projects added manually (not from scrapers)
        ScraperSource::updateOrCreate(
            ['code' => 'manual'],
            [
            'name' => 'Manual Entry',
            'base_url' => null,
            'endpoint_pattern' => null,
            'is_active' => false,
            'rate_limit' => 0,
            'timeout' => 0,
            'retry_attempts' => 0,
            'headers' => [],
            'field_mapping' => [],
            'metadata' => [
                'description' => 'Projects added manually through the admin interface',
            ],
            'scraper_class' => null,
            'version' => '1.0.0',
        ]);

        $this->command->info('Manual source created successfully.');

        ScraperSource::updateOrCreate(
            ['code' => 'dime'],
            [
            'name' => 'DIME.gov.ph',
            'base_url' => 'https://www.dime.gov.ph/_next/data/Vd6FZjlpzlPnb-KLFR5il',
            'endpoint_pattern' => '/project/{id}.json',
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

        ScraperSource::updateOrCreate(
            ['code' => 'sumbongsapangulo'],
            [
            'name' => 'SumbongSaPangulo.ph',
            'base_url' => 'https://www.sumbongsapangulo.ph/api',
            'endpoint_pattern' => '/projects/{id}',
            'is_active' => true,
            'rate_limit' => 10,
            'timeout' => 30,
            'retry_attempts' => 3,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'USISA-Scraper/1.0',
            ],
            'field_mapping' => [
                'title' => 'ProjectName',
                'description' => 'Description',
                'status' => 'Status',
                'cost' => 'Cost',
                'contractor' => 'ContractorName',
                'location' => 'ProjectLocation',
                'progress' => 'PhysicalProgress',
                'date_started' => 'DateStarted',
                'completion_date' => 'ContractCompletionDate',
            ],
            'metadata' => [
                'rate_limit_delay' => 60,
                'batch_size' => 50,
                'supports_pascal_case' => true,
                'supports_camel_case' => true,
            ],
            'scraper_class' => 'App\\Services\\Scrapers\\SumbongSaPanguloScraperStrategy',
            'version' => '1.0.0',
        ]);

        $this->command->info('Sumbong Sa Pangulo scraper source created successfully.');
    }
}