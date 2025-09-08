<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ScraperJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyDataIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:verify
                            {--source= : The scraper source code to verify}
                            {--fix : Attempt to fix issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify data integrity and check for duplicates from scraper operations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data integrity verification...');
        $this->line('');
        
        $issues = [];
        
        // Check for duplicate dime_id values
        $this->info('Checking for duplicate DIME IDs...');
        $duplicateDimeIds = Project::select('dime_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('dime_id')
            ->groupBy('dime_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        if ($duplicateDimeIds->count() > 0) {
            $this->error("Found {$duplicateDimeIds->count()} duplicate DIME IDs:");
            foreach ($duplicateDimeIds as $dup) {
                $this->warn("  - DIME ID {$dup->dime_id}: {$dup->count} occurrences");
                $issues[] = ['type' => 'duplicate_dime_id', 'id' => $dup->dime_id, 'count' => $dup->count];
                
                if ($this->output->isVerbose()) {
                    $projects = Project::where('dime_id', $dup->dime_id)->get(['id', 'project_name', 'created_at', 'updated_at']);
                    foreach ($projects as $project) {
                        $this->line("    - Project #{$project->id}: {$project->project_name} (created: {$project->created_at})");
                    }
                }
            }
        } else {
            $this->info('✓ No duplicate DIME IDs found');
        }
        
        // Check for duplicate project_code values
        $this->info('Checking for duplicate project codes...');
        $duplicateCodes = Project::select('project_code', DB::raw('COUNT(*) as count'))
            ->whereNotNull('project_code')
            ->groupBy('project_code')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        if ($duplicateCodes->count() > 0) {
            $this->error("Found {$duplicateCodes->count()} duplicate project codes:");
            foreach ($duplicateCodes as $dup) {
                $this->warn("  - Project Code '{$dup->project_code}': {$dup->count} occurrences");
                $issues[] = ['type' => 'duplicate_project_code', 'code' => $dup->project_code, 'count' => $dup->count];
                
                if ($this->output->isVerbose()) {
                    $projects = Project::where('project_code', $dup->project_code)->get(['id', 'project_name', 'dime_id', 'created_at']);
                    foreach ($projects as $project) {
                        $this->line("    - Project #{$project->id}: {$project->project_name} (DIME ID: {$project->dime_id})");
                    }
                }
            }
        } else {
            $this->info('✓ No duplicate project codes found');
        }
        
        // Check for projects with same name but different IDs
        $this->info('Checking for duplicate project names...');
        $duplicateNames = Project::select('project_name', DB::raw('COUNT(*) as count'))
            ->groupBy('project_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        if ($duplicateNames->count() > 0) {
            $this->warn("Found {$duplicateNames->count()} duplicate project names (may be legitimate):");
            foreach ($duplicateNames->take(10) as $dup) {
                $this->line("  - '{$dup->project_name}': {$dup->count} occurrences");
            }
            if ($duplicateNames->count() > 10) {
                $this->line("  ... and " . ($duplicateNames->count() - 10) . " more");
            }
        }
        
        // Check for orphaned records (projects without required relationships)
        $this->info('Checking for orphaned records...');
        $orphanedProjects = Project::whereNull('program_id')
            ->orWhereNull('data_source')
            ->count();
        
        if ($orphanedProjects > 0) {
            $this->warn("Found {$orphanedProjects} projects with missing required fields");
            $issues[] = ['type' => 'orphaned_projects', 'count' => $orphanedProjects];
        } else {
            $this->info('✓ No orphaned projects found');
        }
        
        // Check for overlapping scraper jobs
        $this->info('Checking for overlapping scraper jobs...');
        $overlappingJobs = $this->checkOverlappingJobs();
        if ($overlappingJobs->count() > 0) {
            $this->warn("Found {$overlappingJobs->count()} overlapping job pairs:");
            foreach ($overlappingJobs as $pair) {
                $this->line("  - Job #{$pair['job1']} ({$pair['range1']}) overlaps with Job #{$pair['job2']} ({$pair['range2']})");
            }
            $issues[] = ['type' => 'overlapping_jobs', 'count' => $overlappingJobs->count()];
        } else {
            $this->info('✓ No overlapping jobs found');
        }
        
        // Check data freshness
        $this->info('Checking data freshness...');
        $staleData = Project::where('last_synced_at', '<', now()->subDays(30))->count();
        $recentData = Project::where('last_synced_at', '>', now()->subDay())->count();
        
        $this->line("  - Projects synced in last 24 hours: {$recentData}");
        $this->line("  - Projects not synced in 30+ days: {$staleData}");
        
        // Summary
        $this->line('');
        $this->info(str_repeat('=', 50));
        if (empty($issues)) {
            $this->info('✓ Data integrity check passed! No issues found.');
        } else {
            $this->error('✗ Data integrity issues found:');
            foreach ($issues as $issue) {
                $this->warn("  - {$issue['type']}: {$issue['count']} issues");
            }
            
            if ($this->option('fix')) {
                $this->line('');
                if ($this->confirm('Do you want to attempt to fix these issues?')) {
                    $this->fixIssues($issues);
                }
            }
        }
        
        // Statistics
        $this->line('');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Projects', Project::count()],
                ['Projects with DIME ID', Project::whereNotNull('dime_id')->count()],
                ['Projects with Code', Project::whereNotNull('project_code')->count()],
                ['Unique DIME IDs', Project::whereNotNull('dime_id')->distinct('dime_id')->count()],
                ['Unique Project Codes', Project::whereNotNull('project_code')->distinct('project_code')->count()],
                ['Total Scraper Jobs', ScraperJob::count()],
                ['Completed Jobs', ScraperJob::where('status', 'completed')->count()],
            ]
        );
        
        return Command::SUCCESS;
    }
    
    protected function checkOverlappingJobs()
    {
        $jobs = ScraperJob::orderBy('source_id')->orderBy('start_id')->get();
        $overlapping = collect();
        
        foreach ($jobs as $i => $job1) {
            foreach ($jobs as $j => $job2) {
                if ($i >= $j) continue;
                if ($job1->source_id !== $job2->source_id) continue;
                
                // Check if ranges overlap
                if (($job1->start_id <= $job2->end_id && $job1->end_id >= $job2->start_id)) {
                    $overlapping->push([
                        'job1' => $job1->id,
                        'range1' => "{$job1->start_id}-{$job1->end_id}",
                        'job2' => $job2->id,
                        'range2' => "{$job2->start_id}-{$job2->end_id}",
                    ]);
                }
            }
        }
        
        return $overlapping;
    }
    
    protected function fixIssues(array $issues)
    {
        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'duplicate_dime_id':
                    $this->fixDuplicateDimeId($issue['id']);
                    break;
                case 'duplicate_project_code':
                    $this->fixDuplicateProjectCode($issue['code']);
                    break;
                case 'orphaned_projects':
                    $this->fixOrphanedProjects();
                    break;
            }
        }
        
        $this->info('Fix attempt completed. Please run verification again to check results.');
    }
    
    protected function fixDuplicateDimeId($dimeId)
    {
        $projects = Project::where('dime_id', $dimeId)
            ->orderBy('last_synced_at', 'desc')
            ->get();
        
        if ($projects->count() <= 1) return;
        
        // Keep the most recently synced one
        $keep = $projects->first();
        $this->info("Keeping project #{$keep->id} for DIME ID {$dimeId} (most recent sync)");
        
        // Remove others
        foreach ($projects->skip(1) as $project) {
            $this->warn("  - Removing duplicate project #{$project->id}");
            $project->delete();
        }
    }
    
    protected function fixDuplicateProjectCode($code)
    {
        $projects = Project::where('project_code', $code)
            ->orderBy('last_synced_at', 'desc')
            ->get();
        
        if ($projects->count() <= 1) return;
        
        // Keep the most recently synced one
        $keep = $projects->first();
        $this->info("Keeping project #{$keep->id} for code '{$code}' (most recent sync)");
        
        // Remove others
        foreach ($projects->skip(1) as $project) {
            $this->warn("  - Removing duplicate project #{$project->id}");
            $project->delete();
        }
    }
    
    protected function fixOrphanedProjects()
    {
        // Set default program for orphaned projects
        $defaultProgramId = \App\Models\Program::firstOrCreate(
            ['name' => 'Unknown'],
            ['abbreviation' => 'UNK', 'description' => 'Unknown program']
        )->id;
        
        $updated = Project::whereNull('program_id')
            ->update(['program_id' => $defaultProgramId]);
        
        $this->info("Updated {$updated} projects with default program");
        
        // Set data source for projects without one
        $updated = Project::whereNull('data_source')
            ->update(['data_source' => 'manual']);
        
        $this->info("Updated {$updated} projects with default data source");
    }
}