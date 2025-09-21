<?php

namespace App\Console\Commands;

use App\Jobs\ScrapePSGCData;
use App\Services\Scrapers\PSGC\PSGCScraperOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapePSGCCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psgc:scrape
                            {type? : Specific type to scrape (regions|provinces|cities|municipalities|barangays|all)}
                            {--queue : Run the scraper in the queue}
                            {--force : Force scraping even if data was recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape PSGC geographic data from PSA website';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type') ?? 'all';
        $useQueue = $this->option('queue');
        $force = $this->option('force');

        $orchestrator = new PSGCScraperOrchestrator();

        // Check if any scraper is running
        if (!$force && $orchestrator->hasRunningJobs()) {
            $this->error('A PSGC scraper is already running. Use --force to override.');
            return Command::FAILURE;
        }

        $validTypes = array_merge($orchestrator->getAvailableTypes(), ['all']);
        if (!in_array($type, $validTypes)) {
            $this->error("Invalid type. Valid types: " . implode(', ', $validTypes));
            return Command::FAILURE;
        }

        if ($useQueue) {
            $this->info("Dispatching PSGC scraper job to queue...");

            if ($type === 'all') {
                ScrapePSGCData::dispatch();
            } else {
                ScrapePSGCData::dispatch([$type]);
            }

            $this->info("Job dispatched successfully.");
            return Command::SUCCESS;
        }

        // Run synchronously
        $this->info("Starting PSGC data scraping for: {$type}");
        $this->newLine();

        $bar = null;

        try {
            if ($type === 'all') {
                $types = $orchestrator->getAvailableTypes();
                $bar = $this->output->createProgressBar(count($types));
                $bar->start();

                foreach ($types as $scraperType) {
                    $this->newLine();
                    $this->info("Scraping {$scraperType}...");

                    $result = $orchestrator->runSpecific($scraperType);

                    if ($result['status'] === 'completed') {
                        $this->info("✓ {$scraperType}: Created: {$result['stats']['created']}, Updated: {$result['stats']['updated']}, Errors: {$result['stats']['errors']}");
                    } else {
                        $this->error("✗ {$scraperType}: {$result['error']}");
                    }

                    $bar->advance();
                }

                $bar->finish();
            } else {
                $result = $orchestrator->runSpecific($type);

                if ($result['status'] === 'completed') {
                    $this->info("Scraping completed successfully!");
                    $this->table(
                        ['Metric', 'Value'],
                        [
                            ['Created', $result['stats']['created']],
                            ['Updated', $result['stats']['updated']],
                            ['Errors', $result['stats']['errors']],
                            ['Duration', $result['duration'] . ' seconds'],
                        ]
                    );
                } else {
                    $this->error("Scraping failed: {$result['error']}");
                    return Command::FAILURE;
                }
            }

            $this->newLine();
            $this->info("All operations completed.");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error("PSGC scraping command failed: " . $e->getMessage());
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}