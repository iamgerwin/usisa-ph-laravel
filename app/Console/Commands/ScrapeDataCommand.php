<?php

namespace App\Console\Commands;

use App\Enums\ScraperJobStatus;
use App\Models\ScraperJob;
use App\Models\ScraperSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:data
                            {--source= : The scraper source code (e.g., dime)}
                            {--start= : Starting ID for scraping}
                            {--end= : Ending ID for scraping}
                            {--chunk=100 : Number of records to process in each batch}
                            {--delay=1 : Delay in seconds between requests}
                            {--retry=3 : Number of retry attempts for failed requests}
                            {--resume : Resume from last failed/paused job}
                            {--job= : Specific job ID to resume}
                            {--dry-run : Run without saving to database}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape data from configured sources';

    protected ScraperSource $source;
    protected ScraperJob $job;
    protected $strategy;
    protected bool $dryRun = false;
    protected bool $verbose = false;
    protected int $delay = 1;
    protected int $chunkSize = 100;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->verbose = $this->option('verbose');
        $this->delay = (int) $this->option('delay');
        $this->chunkSize = (int) $this->option('chunk');

        try {
            $this->initializeSource();
            $this->initializeJob();
            $this->initializeStrategy();
            
            if ($this->dryRun) {
                $this->warn('Running in DRY RUN mode - no data will be saved');
            }
            
            $this->info("Starting scraper for {$this->source->name}");
            $this->info("Range: {$this->job->start_id} to {$this->job->end_id}");
            $this->info("Chunk size: {$this->chunkSize}");
            
            $this->performScraping();
            
            $this->displaySummary();
            
        } catch (\Exception $e) {
            $this->error("Scraping failed: {$e->getMessage()}");
            
            if ($this->job ?? null) {
                $this->job->markAsFailed($e->getMessage());
            }
            
            Log::error('Scraping command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }

    protected function initializeSource(): void
    {
        $sourceCode = $this->option('source');
        
        if (!$sourceCode) {
            $sources = ScraperSource::active()->pluck('name', 'code')->toArray();
            
            if (empty($sources)) {
                throw new \Exception('No active scraper sources found');
            }
            
            $sourceCode = $this->choice('Select a source to scrape', array_keys($sources));
        }
        
        $this->source = ScraperSource::where('code', $sourceCode)
            ->where('is_active', true)
            ->firstOrFail();
        
        if (!$this->source->scraper_class || !class_exists($this->source->scraper_class)) {
            throw new \Exception("Scraper class not found: {$this->source->scraper_class}");
        }
    }

    protected function initializeJob(): void
    {
        if ($this->option('resume') || $this->option('job')) {
            $this->resumeExistingJob();
        } else {
            $this->createNewJob();
        }
        
        if (!$this->dryRun) {
            $this->job->markAsRunning();
        }
    }

    protected function resumeExistingJob(): void
    {
        $jobId = $this->option('job');
        
        if ($jobId) {
            $this->job = ScraperJob::findOrFail($jobId);
        } else {
            $this->job = ScraperJob::where('source_id', $this->source->id)
                ->whereIn('status', [ScraperJobStatus::PAUSED, ScraperJobStatus::FAILED])
                ->latest()
                ->firstOrFail();
        }
        
        if (!$this->job->canResume()) {
            throw new \Exception("Job {$this->job->id} cannot be resumed (status: {$this->job->status->value})");
        }
        
        $this->info("Resuming job {$this->job->id} from ID {$this->job->current_id}");
    }

    protected function createNewJob(): void
    {
        $startId = $this->option('start') ?? $this->ask('Enter starting ID', 1);
        $endId = $this->option('end') ?? $this->ask('Enter ending ID', 1000);
        
        if ($startId > $endId) {
            throw new \Exception('Start ID must be less than or equal to End ID');
        }
        
        if ($this->source->hasRunningJob()) {
            if (!$this->confirm('Source has a running job. Continue anyway?')) {
                throw new \Exception('Aborted due to existing running job');
            }
        }
        
        $this->job = ScraperJob::create([
            'source_id' => $this->source->id,
            'start_id' => $startId,
            'end_id' => $endId,
            'current_id' => $startId,
            'chunk_size' => $this->chunkSize,
            'status' => ScraperJobStatus::PENDING,
            'triggered_by' => 'console',
        ]);
    }

    protected function initializeStrategy(): void
    {
        $this->strategy = new $this->source->scraper_class($this->source);
        $this->strategy->setJob($this->job);
    }

    protected function performScraping(): void
    {
        $currentId = $this->job->current_id ?? $this->job->start_id;
        $endId = $this->job->end_id;
        $modelClass = $this->strategy->getModelClass();
        $uniqueField = $this->strategy->getUniqueField();
        
        $progressBar = $this->output->createProgressBar($endId - $currentId + 1);
        $progressBar->start();
        
        while ($currentId <= $endId) {
            $batchEnd = min($currentId + $this->chunkSize - 1, $endId);
            $ids = range($currentId, $batchEnd);
            
            if ($this->verbose) {
                $this->line("\nProcessing batch: {$currentId} to {$batchEnd}");
            }
            
            $results = $this->strategy->scrapeBatch($ids);
            
            foreach ($results as $data) {
                try {
                    if (!$this->dryRun) {
                        $this->saveData($data, $modelClass, $uniqueField);
                    } else {
                        $this->job->incrementSuccess();
                    }
                } catch (\Exception $e) {
                    $this->job->incrementError();
                    $this->job->logError($data['source_id'] ?? 0, $e->getMessage());
                    
                    if ($this->verbose) {
                        $this->error("\nError saving data: {$e->getMessage()}");
                    }
                }
            }
            
            $skipped = count($ids) - $results->count();
            if ($skipped > 0) {
                $this->job->incrementSkip($skipped);
            }
            
            $currentId = $batchEnd + 1;
            
            if (!$this->dryRun) {
                $this->job->updateProgress($currentId);
            }
            
            $progressBar->advance(count($ids));
            
            if ($currentId <= $endId && $this->delay > 0) {
                sleep($this->delay);
            }
            
            if ($this->shouldStop()) {
                $this->warn("\nStopping scraper...");
                $this->job->markAsPaused();
                break;
            }
        }
        
        $progressBar->finish();
        $this->line('');
        
        if ($currentId > $endId && !$this->dryRun) {
            $this->job->markAsCompleted();
        }
    }

    protected function saveData(array $data, string $modelClass, string $uniqueField): void
    {
        DB::transaction(function () use ($data, $modelClass, $uniqueField) {
            $uniqueValue = $data[$uniqueField] ?? null;
            
            if (!$uniqueValue) {
                throw new \Exception("Unique field {$uniqueField} not found in data");
            }
            
            $conditions = [
                $uniqueField => $uniqueValue,
            ];
            
            // For DIME scraper, also check by project_code as secondary unique
            if ($uniqueField === 'dime_id' && isset($data['project_code'])) {
                $existing = $modelClass::where($uniqueField, $uniqueValue)
                    ->orWhere('project_code', $data['project_code'])
                    ->first();
            } else {
                $existing = $modelClass::where($conditions)->first();
            }
            
            if ($existing) {
                // Update dime_id if it was matched by project_code
                if ($uniqueField === 'dime_id' && !$existing->dime_id) {
                    $data['dime_id'] = $uniqueValue;
                }
                
                $existing->update($data);
                $this->job->incrementUpdate();
                
                if ($this->verbose) {
                    $this->info("Updated: {$uniqueField} = {$uniqueValue}");
                }
            } else {
                $modelClass::create($data);
                $this->job->incrementCreate();
                
                if ($this->verbose) {
                    $this->info("Created: {$uniqueField} = {$uniqueValue}");
                }
            }
        });
    }

    protected function shouldStop(): bool
    {
        if (file_exists(storage_path('scraper.stop'))) {
            unlink(storage_path('scraper.stop'));
            return true;
        }
        
        return false;
    }

    protected function displaySummary(): void
    {
        $this->info("\n" . str_repeat('=', 50));
        $this->info("Scraping Summary for Job #{$this->job->id}");
        $this->info(str_repeat('=', 50));
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Success', $this->job->success_count],
                ['Errors', $this->job->error_count],
                ['Skipped', $this->job->skip_count],
                ['Created', $this->job->create_count],
                ['Updated', $this->job->update_count],
                ['Total Processed', $this->job->total_processed],
                ['Progress', "{$this->job->progress_percentage}%"],
                ['Duration', $this->job->formatted_duration ?? 'N/A'],
            ]
        );
        
        if ($this->job->error_count > 0 && $this->verbose) {
            $this->warn("\nErrors encountered:");
            $errors = array_slice($this->job->errors ?? [], -10);
            foreach ($errors as $error) {
                $this->line("  - ID {$error['id']}: {$error['message']}");
            }
        }
    }
}