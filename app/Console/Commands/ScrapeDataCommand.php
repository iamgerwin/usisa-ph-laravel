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
                            {--dry-run : Run without saving to database}';

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
    protected int $delay = 1;
    protected int $chunkSize = 100;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
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
        
        // Implement job locking - prevent concurrent jobs for same source and overlapping ID ranges
        $this->checkForConflictingJobs($startId, $endId);
        
        if ($this->source->hasRunningJob()) {
            $runningJobs = $this->source->runningJobs()->get();
            $this->error('Source has running job(s):');
            foreach ($runningJobs as $job) {
                $this->warn("  - Job #{$job->id}: Range {$job->start_id}-{$job->end_id}, Current: {$job->current_id}");
            }
            
            if (!$this->confirm('This may cause duplicate data. Are you sure you want to continue?')) {
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
            
            if ($this->output->isVerbose()) {
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
                    
                    if ($this->output->isVerbose()) {
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
                
                // Save progress to database every 10 batches to ensure resumability
                static $batchCounter = 0;
                $batchCounter++;
                if ($batchCounter % 10 === 0) {
                    $this->job->save();
                    
                    // Also check if process has been running too long (over 2 hours)
                    $runtime = $this->job->started_at ? now()->diffInMinutes($this->job->started_at) : 0;
                    if ($runtime > 120) {
                        $this->warn("\nAuto-pausing after 2 hours of runtime for safety...");
                        $this->job->markAsPaused();
                        $this->info("Resume with: php artisan scrape:data --resume --source={$this->source->code}");
                        break;
                    }
                }
            }
            
            $progressBar->advance(count($ids));
            
            if ($currentId <= $endId && $this->delay > 0) {
                sleep($this->delay);
            }
            
            if ($this->shouldStop()) {
                $this->warn("\nStopping scraper...");
                $this->job->markAsPaused();
                $this->info("Resume with: php artisan scrape:data --resume --source={$this->source->code}");
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
            
            // Always update last_synced_at to track when we last fetched this record
            $data['last_synced_at'] = now();
            
            // For DIME scraper, handle multiple unique constraints
            if ($uniqueField === 'dime_id') {
                // Use lockForUpdate to prevent race conditions
                $query = $modelClass::query()->lockForUpdate();
                
                // Check by dime_id first
                $query->where($uniqueField, $uniqueValue);
                
                // Also check by project_code if available
                if (isset($data['project_code']) && $data['project_code']) {
                    $query->orWhere('project_code', $data['project_code']);
                }
                
                $existing = $query->first();
                
                if ($existing) {
                    // Ensure we keep the dime_id even if matched by project_code
                    $data['dime_id'] = $uniqueValue;
                    
                    // Compare timestamps to ensure we're updating with newer data
                    $existingSyncTime = $existing->last_synced_at;
                    $shouldUpdate = true;
                    
                    // If the existing record was synced very recently (within 1 hour), skip update
                    if ($existingSyncTime && $existingSyncTime->diffInHours(now()) < 1) {
                        $shouldUpdate = false;
                        $this->job->incrementSkip();
                        
                        if ($this->output->isVerbose()) {
                            $this->info("Skipped (recently synced): {$uniqueField} = {$uniqueValue}");
                        }
                    }
                    
                    if ($shouldUpdate) {
                        $existing->update($data);
                        $this->job->incrementUpdate();
                        
                        if ($this->output->isVerbose()) {
                            $this->info("Updated: {$uniqueField} = {$uniqueValue}");
                        }
                    }
                } else {
                    // New record
                    $modelClass::create($data);
                    $this->job->incrementCreate();
                    
                    if ($this->output->isVerbose()) {
                        $this->info("Created: {$uniqueField} = {$uniqueValue}");
                    }
                }
            } else {
                // Generic unique field handling for other scrapers
                // Generic unique field handling for other scrapers with lock
                $existing = $modelClass::where($uniqueField, $uniqueValue)
                    ->lockForUpdate()
                    ->first();
                
                if ($existing) {
                    $existing->update($data);
                    $this->job->incrementUpdate();
                    
                    if ($this->output->isVerbose()) {
                        $this->info("Updated: {$uniqueField} = {$uniqueValue}");
                    }
                } else {
                    $modelClass::create($data);
                    $this->job->incrementCreate();
                    
                    if ($this->output->isVerbose()) {
                        $this->info("Created: {$uniqueField} = {$uniqueValue}");
                    }
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

    /**
     * Check for conflicting jobs that might cause duplicate data
     */
    protected function checkForConflictingJobs(int $startId, int $endId): void
    {
        // Check for any jobs (running, paused, or pending) with overlapping ID ranges
        $conflictingJobs = ScraperJob::where('source_id', $this->source->id)
            ->whereIn('status', [ScraperJobStatus::RUNNING, ScraperJobStatus::PENDING, ScraperJobStatus::PAUSED])
            ->where(function ($query) use ($startId, $endId) {
                // Check for overlapping ranges
                $query->where(function ($q) use ($startId, $endId) {
                    // New range starts within existing range
                    $q->where('start_id', '<=', $startId)
                      ->where('end_id', '>=', $startId);
                })->orWhere(function ($q) use ($startId, $endId) {
                    // New range ends within existing range
                    $q->where('start_id', '<=', $endId)
                      ->where('end_id', '>=', $endId);
                })->orWhere(function ($q) use ($startId, $endId) {
                    // New range completely contains existing range
                    $q->where('start_id', '>=', $startId)
                      ->where('end_id', '<=', $endId);
                });
            })
            ->get();
        
        if ($conflictingJobs->isNotEmpty()) {
            $this->error('Found conflicting jobs with overlapping ID ranges:');
            foreach ($conflictingJobs as $job) {
                $this->warn("  - Job #{$job->id} ({$job->status->value}): Range {$job->start_id}-{$job->end_id}, Current: {$job->current_id}");
            }
            
            if (!$this->confirm('Overlapping ranges may cause duplicate data. Continue anyway?')) {
                throw new \Exception('Aborted due to conflicting job ranges');
            }
        }
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
        
        if ($this->job->error_count > 0 && $this->output->isVerbose()) {
            $this->warn("\nErrors encountered:");
            $errors = array_slice($this->job->errors ?? [], -10);
            foreach ($errors as $error) {
                $this->line("  - ID {$error['id']}: {$error['message']}");
            }
        }
    }
}