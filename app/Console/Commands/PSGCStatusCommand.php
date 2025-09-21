<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Services\Scrapers\PSGC\PSGCScraperOrchestrator;
use Illuminate\Console\Command;

class PSGCStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psgc:status
                            {--detailed : Show detailed statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show PSGC data status and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('PSGC Data Status');
        $this->newLine();

        // Database statistics
        $this->displayDatabaseStats();

        // Sync status
        $this->displaySyncStatus();

        // Scraper statistics
        if ($this->option('detailed')) {
            $this->displayScraperStats();
        }

        return Command::SUCCESS;
    }

    private function displayDatabaseStats(): void
    {
        $stats = [
            ['Entity', 'Total Count', 'Active', 'With PSA Code'],
            ['-------', '-----------', '------', '-------------'],
            [
                'Regions',
                Region::count(),
                Region::where('is_active', true)->count(),
                Region::whereNotNull('psa_code')->count(),
            ],
            [
                'Provinces',
                Province::count(),
                Province::where('is_active', true)->count(),
                Province::whereNotNull('psa_code')->count(),
            ],
            [
                'Cities',
                City::where('type', 'city')->count(),
                City::where('type', 'city')->where('is_active', true)->count(),
                City::where('type', 'city')->whereNotNull('psa_code')->count(),
            ],
            [
                'Municipalities',
                City::where('type', 'municipality')->count(),
                City::where('type', 'municipality')->where('is_active', true)->count(),
                City::where('type', 'municipality')->whereNotNull('psa_code')->count(),
            ],
            [
                'Barangays',
                Barangay::count(),
                Barangay::where('is_active', true)->count(),
                Barangay::whereNotNull('psa_code')->count(),
            ],
        ];

        $this->table(
            array_shift($stats),
            $stats
        );
        $this->newLine();
    }

    private function displaySyncStatus(): void
    {
        $this->info('Last Sync Status:');

        $entities = [
            'Regions' => Region::class,
            'Provinces' => Province::class,
            'Cities' => City::class,
            'Barangays' => Barangay::class,
        ];

        $syncData = [];
        foreach ($entities as $name => $class) {
            $lastSync = $class::whereNotNull('psa_synced_at')
                ->orderBy('psa_synced_at', 'desc')
                ->first();

            $syncData[] = [
                $name,
                $lastSync ? $lastSync->psa_synced_at->format('Y-m-d H:i:s') : 'Never',
                $lastSync ? $lastSync->psa_synced_at->diffForHumans() : '-',
            ];
        }

        $this->table(
            ['Entity', 'Last Sync', 'Time Ago'],
            $syncData
        );
        $this->newLine();
    }

    private function displayScraperStats(): void
    {
        $this->info('Scraper Job Statistics:');

        $orchestrator = new PSGCScraperOrchestrator();
        $stats = $orchestrator->getStatistics();

        $tableData = [];
        foreach ($stats as $type => $stat) {
            $tableData[] = [
                ucfirst($type),
                $stat['total_jobs'] ?? 0,
                $stat['completed_jobs'] ?? 0,
                $stat['failed_jobs'] ?? 0,
                $stat['total_scraped'] ?? 0,
                $stat['total_errors'] ?? 0,
                $stat['last_run'] ? $stat['last_run']->format('Y-m-d H:i:s') : 'Never',
            ];
        }

        $this->table(
            ['Type', 'Total Jobs', 'Completed', 'Failed', 'Scraped', 'Errors', 'Last Run'],
            $tableData
        );
        $this->newLine();
    }
}