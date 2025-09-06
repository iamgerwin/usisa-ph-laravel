<?php

namespace Tests\Feature;

use App\Enums\ScraperJobStatus;
use App\Models\Project;
use App\Models\ScraperJob;
use App\Models\ScraperSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScraperCommandTest extends TestCase
{
    use RefreshDatabase;

    protected ScraperSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->source = ScraperSource::create([
            'code' => 'test',
            'name' => 'Test Source',
            'base_url' => 'https://api.example.com',
            'endpoint_pattern' => '/projects/{id}',
            'is_active' => true,
            'rate_limit' => 10,
            'timeout' => 30,
            'retry_attempts' => 3,
            'scraper_class' => 'App\\Services\\Scrapers\\DimeScraperStrategy',
        ]);
    }

    public function test_scraper_command_creates_new_job(): void
    {
        $this->artisan('scrape:data', [
            '--source' => 'test',
            '--start' => 1,
            '--end' => 10,
            '--chunk' => 5,
            '--dry-run' => true,
        ])
        ->expectsOutput('Starting scraper for Test Source')
        ->expectsOutput('Range: 1 to 10')
        ->expectsOutput('Chunk size: 5')
        ->assertSuccessful();
        
        $this->assertDatabaseHas('scraper_jobs', [
            'source_id' => $this->source->id,
            'start_id' => 1,
            'end_id' => 10,
            'chunk_size' => 5,
        ]);
    }

    public function test_scraper_command_resumes_existing_job(): void
    {
        $job = ScraperJob::create([
            'source_id' => $this->source->id,
            'start_id' => 1,
            'end_id' => 100,
            'current_id' => 50,
            'chunk_size' => 10,
            'status' => ScraperJobStatus::PAUSED,
        ]);
        
        $this->artisan('scrape:data', [
            '--source' => 'test',
            '--resume' => true,
            '--dry-run' => true,
        ])
        ->expectsOutput("Resuming job {$job->id} from ID 50")
        ->assertSuccessful();
    }

    public function test_scraper_command_fails_with_invalid_source(): void
    {
        $this->artisan('scrape:data', [
            '--source' => 'invalid',
        ])
        ->expectsOutput('No records found.')
        ->assertFailed();
    }

    public function test_scraper_command_validates_id_range(): void
    {
        $this->artisan('scrape:data', [
            '--source' => 'test',
            '--start' => 100,
            '--end' => 10,
        ])
        ->expectsOutput('Start ID must be less than or equal to End ID')
        ->assertFailed();
    }

    public function test_scraper_job_status_transitions(): void
    {
        $job = new ScraperJob([
            'status' => ScraperJobStatus::PENDING,
        ]);
        
        $this->assertTrue($job->canResume());
        
        $job->status = ScraperJobStatus::RUNNING;
        $this->assertTrue($job->isRunning());
        $this->assertTrue($job->canCancel());
        
        $job->status = ScraperJobStatus::COMPLETED;
        $this->assertTrue($job->isCompleted());
        $this->assertFalse($job->canResume());
        
        $job->status = ScraperJobStatus::FAILED;
        $this->assertTrue($job->hasFailed());
        $this->assertTrue($job->canResume());
    }

    public function test_scraper_source_builds_correct_url(): void
    {
        $url = $this->source->buildUrl(123);
        $this->assertEquals('https://api.example.com/projects/123', $url);
        
        $sourceWithoutPattern = ScraperSource::make([
            'base_url' => 'https://api.example.com',
            'endpoint_pattern' => null,
        ]);
        
        $url = $sourceWithoutPattern->buildUrl(123);
        $this->assertEquals('https://api.example.com', $url);
    }

    public function test_scraper_job_progress_calculation(): void
    {
        $job = new ScraperJob([
            'start_id' => 1,
            'end_id' => 100,
            'current_id' => 50,
        ]);
        
        $this->assertEquals(50, $job->progress_percentage);
        $this->assertEquals(50, $job->remaining_count);
        
        $job->current_id = 100;
        $this->assertEquals(100, $job->progress_percentage);
        $this->assertEquals(0, $job->remaining_count);
    }

    public function test_scraper_job_counter_methods(): void
    {
        $job = ScraperJob::create([
            'source_id' => $this->source->id,
            'start_id' => 1,
            'end_id' => 10,
            'status' => ScraperJobStatus::RUNNING,
        ]);
        
        $job->incrementSuccess(5);
        $job->incrementError(2);
        $job->incrementSkip(1);
        $job->incrementUpdate(3);
        $job->incrementCreate(2);
        
        $job->refresh();
        
        $this->assertEquals(5, $job->success_count);
        $this->assertEquals(2, $job->error_count);
        $this->assertEquals(1, $job->skip_count);
        $this->assertEquals(3, $job->update_count);
        $this->assertEquals(2, $job->create_count);
        $this->assertEquals(8, $job->total_processed);
    }
}