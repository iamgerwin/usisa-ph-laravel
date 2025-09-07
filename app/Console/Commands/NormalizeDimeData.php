<?php

namespace App\Console\Commands;

use App\Models\Contractor;
use App\Models\ImplementingOffice;
use App\Models\Project;
use App\Models\Program;
use App\Models\SourceOfFund;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NormalizeDimeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dime:normalize-data 
                            {--batch=100 : Number of projects to process at a time}
                            {--dry-run : Preview what would be normalized without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize DIME data from metadata JSON to relational tables';

    protected $stats = [
        'projects_processed' => 0,
        'implementing_offices_created' => 0,
        'contractors_created' => 0,
        'source_of_funds_created' => 0,
        'relationships_created' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting DIME data normalization...');
        
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }
        
        $totalProjects = Project::whereNotNull('metadata')
            ->where('data_source', 'dime')
            ->count();
            
        if ($totalProjects === 0) {
            $this->info('No DIME projects found with metadata to normalize.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$totalProjects} DIME projects to process");
        
        $progressBar = $this->output->createProgressBar($totalProjects);
        $progressBar->start();
        
        Project::whereNotNull('metadata')
            ->where('data_source', 'dime')
            ->chunk($batchSize, function ($projects) use ($progressBar, $isDryRun) {
                foreach ($projects as $project) {
                    try {
                        if (!$isDryRun) {
                            DB::beginTransaction();
                        }
                        
                        $this->normalizeProject($project, $isDryRun);
                        
                        if (!$isDryRun) {
                            DB::commit();
                        }
                        
                        $this->stats['projects_processed']++;
                    } catch (\Exception $e) {
                        if (!$isDryRun) {
                            DB::rollBack();
                        }
                        
                        $this->stats['errors']++;
                        Log::error('Failed to normalize project ' . $project->uuid, [
                            'error' => $e->getMessage(),
                            'project_id' => $project->id,
                            'dime_id' => $project->dime_id,
                        ]);
                    }
                    
                    $progressBar->advance();
                }
            });
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->displayStatistics();
        
        return Command::SUCCESS;
    }
    
    protected function normalizeProject(Project $project, bool $isDryRun): void
    {
        $metadata = $project->metadata;
        
        if (!$metadata) {
            return;
        }
        
        // Process implementing offices
        if (isset($metadata['implementing_offices']) && is_array($metadata['implementing_offices'])) {
            $this->processImplementingOffices($project, $metadata['implementing_offices'], $isDryRun);
        }
        
        // Process contractors
        if (isset($metadata['contractors']) && is_array($metadata['contractors'])) {
            $this->processContractors($project, $metadata['contractors'], $isDryRun);
        }
        
        // Process source of funds
        if (isset($metadata['source_of_funds']) && is_array($metadata['source_of_funds'])) {
            $this->processSourceOfFunds($project, $metadata['source_of_funds'], $isDryRun);
        }
        
        // Process program
        if (isset($metadata['program']) && is_array($metadata['program'])) {
            $this->processProgram($project, $metadata['program'], $isDryRun);
        }
    }
    
    protected function processImplementingOffices(Project $project, array $offices, bool $isDryRun): void
    {
        foreach ($offices as $officeData) {
            if (empty($officeData['name'])) {
                continue;
            }
            
            $office = null;
            
            // Try to find by dime_id first
            if (!empty($officeData['id'])) {
                $office = ImplementingOffice::where('dime_id', $officeData['id'])->first();
            }
            
            // If not found, try by name
            if (!$office) {
                $office = ImplementingOffice::where('name', $officeData['name'])->first();
            }
            
            // Create if not exists
            if (!$office && !$isDryRun) {
                $office = ImplementingOffice::create([
                    'dime_id' => $officeData['id'] ?? null,
                    'name' => $officeData['name'],
                    'name_abbreviation' => $officeData['abbreviation'] ?? null,
                    'logo_url' => $officeData['logo_url'] ?? null,
                    'is_active' => true,
                ]);
                
                $this->stats['implementing_offices_created']++;
            }
            
            // Attach to project
            if ($office && !$isDryRun) {
                if (!$project->implementingOffices()->where('implementing_office_uuid', $office->uuid)->exists()) {
                    $project->implementingOffices()->attach($office->uuid);
                    $this->stats['relationships_created']++;
                }
            }
        }
    }
    
    protected function processContractors(Project $project, array $contractors, bool $isDryRun): void
    {
        foreach ($contractors as $contractorData) {
            if (empty($contractorData['name'])) {
                continue;
            }
            
            $contractor = null;
            
            // Try to find by dime_id first
            if (!empty($contractorData['id'])) {
                $contractor = Contractor::where('dime_id', $contractorData['id'])->first();
            }
            
            // If not found, try by name
            if (!$contractor) {
                $contractor = Contractor::where('name', $contractorData['name'])->first();
            }
            
            // Create if not exists
            if (!$contractor && !$isDryRun) {
                $contractor = Contractor::create([
                    'dime_id' => $contractorData['id'] ?? null,
                    'name' => $contractorData['name'],
                    'name_abbreviation' => $contractorData['abbreviation'] ?? null,
                    'logo_url' => $contractorData['logo_url'] ?? null,
                    'is_active' => true,
                ]);
                
                $this->stats['contractors_created']++;
            }
            
            // Attach to project
            if ($contractor && !$isDryRun) {
                if (!$project->contractors()->where('contractor_uuid', $contractor->uuid)->exists()) {
                    $project->contractors()->attach($contractor->uuid);
                    $this->stats['relationships_created']++;
                }
            }
        }
    }
    
    protected function processSourceOfFunds(Project $project, array $sources, bool $isDryRun): void
    {
        foreach ($sources as $sourceData) {
            if (empty($sourceData['name'])) {
                continue;
            }
            
            $source = null;
            
            // Try to find by dime_id first
            if (!empty($sourceData['id'])) {
                $source = SourceOfFund::where('dime_id', $sourceData['id'])->first();
            }
            
            // If not found, try by name
            if (!$source) {
                $source = SourceOfFund::where('name', $sourceData['name'])->first();
            }
            
            // Create if not exists
            if (!$source && !$isDryRun) {
                $source = SourceOfFund::create([
                    'dime_id' => $sourceData['id'] ?? null,
                    'name' => $sourceData['name'],
                    'name_abbreviation' => $sourceData['abbreviation'] ?? null,
                    'logo_url' => $sourceData['logo_url'] ?? null,
                    'is_active' => true,
                ]);
                
                $this->stats['source_of_funds_created']++;
            }
            
            // Attach to project
            if ($source && !$isDryRun) {
                if (!$project->sourceOfFunds()->where('source_of_fund_uuid', $source->uuid)->exists()) {
                    $project->sourceOfFunds()->attach($source->uuid);
                    $this->stats['relationships_created']++;
                }
            }
        }
    }
    
    protected function processProgram(Project $project, array $programData, bool $isDryRun): void
    {
        if (empty($programData['name']) || $project->program_id) {
            return;
        }
        
        $program = null;
        
        // Try to find by dime_id first
        if (!empty($programData['dime_id'])) {
            $program = Program::where('dime_id', $programData['dime_id'])->first();
        }
        
        // If not found, try by name
        if (!$program) {
            $program = Program::where('name', $programData['name'])->first();
        }
        
        // Update program with dime_id if needed
        if ($program && !$program->dime_id && !empty($programData['dime_id']) && !$isDryRun) {
            $program->update(['dime_id' => $programData['dime_id']]);
        }
        
        // Update project with program_id
        if ($program && !$isDryRun) {
            $project->update(['program_id' => $program->id]);
        }
    }
    
    protected function displayStatistics(): void
    {
        $this->info('Normalization Complete!');
        $this->newLine();
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Projects Processed', $this->stats['projects_processed']],
                ['Implementing Offices Created', $this->stats['implementing_offices_created']],
                ['Contractors Created', $this->stats['contractors_created']],
                ['Source of Funds Created', $this->stats['source_of_funds_created']],
                ['Relationships Created', $this->stats['relationships_created']],
                ['Errors', $this->stats['errors']],
            ]
        );
        
        if ($this->stats['errors'] > 0) {
            $this->warn("There were {$this->stats['errors']} errors during normalization. Check the logs for details.");
        }
    }
}