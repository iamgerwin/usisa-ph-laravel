<?php

namespace App\Console\Commands;

use App\Models\ScraperJob;
use App\Models\Project;
use Illuminate\Console\Command;
use Carbon\Carbon;

class MonitorDimeScraperCommand extends Command
{
    protected $signature = 'dime:monitor
                            {--job-id= : Specific job UUID to monitor}
                            {--live : Live monitoring mode with auto-refresh}
                            {--interval=5 : Refresh interval in seconds for live mode}';

    protected $description = 'Monitor DIME scraper progress and statistics';

    public function handle()
    {
        $jobId = $this->option('job-id');
        $isLive = $this->option('live');
        $interval = (int) $this->option('interval');

        if ($jobId) {
            $this->monitorSpecificJob($jobId, $isLive, $interval);
        } else {
            $this->showOverallStatistics($isLive, $interval);
        }

        return Command::SUCCESS;
    }

    protected function monitorSpecificJob(string $jobId, bool $isLive, int $interval): void
    {
        do {
            $job = ScraperJob::where('uuid', $jobId)
                ->orWhere('id', $jobId)
                ->first();

            if (!$job) {
                $this->error("Job not found: {$jobId}");
                return;
            }

            $this->displayJobDetails($job);

            if ($isLive && $job->status->value !== 'completed' && $job->status->value !== 'failed') {
                sleep($interval);
                $this->line("\033[2J\033[H"); // Clear screen
            }
        } while ($isLive && $job->status->value !== 'completed' && $job->status->value !== 'failed');
    }

    protected function showOverallStatistics(bool $isLive, int $interval): void
    {
        do {
            $this->displayOverallStats();

            if ($isLive) {
                sleep($interval);
                $this->line("\033[2J\033[H"); // Clear screen
            }
        } while ($isLive);
    }

    protected function displayJobDetails(ScraperJob $job): void
    {
        $this->info("=== DIME Scraper Job Monitor ===");
        $this->newLine();

        // Basic Info
        $this->table(
            ['Field', 'Value'],
            [
                ['Job ID', $job->uuid],
                ['Status', $this->getStatusBadge($job->status->value)],
                ['Source', $job->source->name ?? 'DIME'],
                ['Started', $job->created_at->format('Y-m-d H:i:s')],
                ['Duration', $this->getDuration($job)],
            ]
        );

        // Progress
        $total = $job->end_id - $job->start_id;
        $processed = $job->current_id - $job->start_id;
        $percentage = $total > 0 ? round(($processed / $total) * 100, 2) : 0;

        $this->info("Progress: {$processed}/{$total} ({$percentage}%)");
        $this->renderProgressBar($percentage);
        $this->newLine();

        // Statistics
        $this->table(
            ['Metric', 'Count'],
            [
                ['Projects Created', number_format($job->create_count)],
                ['Projects Updated', number_format($job->update_count)],
                ['Errors', number_format($job->error_count)],
                ['Processing Rate', $this->getProcessingRate($job) . ' projects/min'],
            ]
        );

        // Recent Projects
        $recentProjects = Project::where('external_source', 'dime')
            ->where('created_at', '>=', $job->created_at)
            ->latest()
            ->limit(5)
            ->get(['project_name', 'region_name', 'province_name', 'cost', 'created_at']);

        if ($recentProjects->count() > 0) {
            $this->info("Recent Projects:");
            $this->table(
                ['Name', 'Region', 'Province', 'Cost', 'Added'],
                $recentProjects->map(function ($p) {
                    return [
                        substr($p->project_name, 0, 40) . (strlen($p->project_name) > 40 ? '...' : ''),
                        $p->region_name ?? 'N/A',
                        $p->province_name ?? 'N/A',
                        '₱' . number_format($p->cost ?? 0, 2),
                        $p->created_at->diffForHumans(),
                    ];
                })->toArray()
            );
        }

        // Error Messages
        if ($job->error_count > 0 && $job->error_messages) {
            $this->warn("Recent Errors:");
            $errors = json_decode($job->error_messages, true) ?? [];
            foreach (array_slice($errors, -3) as $error) {
                $this->line("  - " . $error);
            }
        }
    }

    protected function displayOverallStats(): void
    {
        $this->info("=== DIME Scraper Overall Statistics ===");
        $this->newLine();

        // Total Projects
        $totalProjects = Project::where('external_source', 'dime')->count();
        $todayProjects = Project::where('external_source', 'dime')
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Geographic Coverage
        $regionsCovered = Project::where('external_source', 'dime')
            ->whereNotNull('region_id')
            ->distinct('region_id')
            ->count('region_id');

        $provincesCovered = Project::where('external_source', 'dime')
            ->whereNotNull('province_id')
            ->distinct('province_id')
            ->count('province_id');

        $citiesCovered = Project::where('external_source', 'dime')
            ->whereNotNull('city_id')
            ->distinct('city_id')
            ->count('city_id');

        // Total Value
        $totalValue = Project::where('external_source', 'dime')
            ->sum('cost');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Projects', number_format($totalProjects)],
                ['Projects Added Today', number_format($todayProjects)],
                ['Total Project Value', '₱' . number_format($totalValue, 2)],
                ['Regions Covered', "{$regionsCovered}/17"],
                ['Provinces Covered', "{$provincesCovered}/81"],
                ['Cities/Municipalities', number_format($citiesCovered)],
            ]
        );

        // Recent Jobs
        $recentJobs = ScraperJob::whereHas('source', function ($q) {
            $q->where('code', 'dime');
        })
            ->latest()
            ->limit(5)
            ->get();

        $this->info("Recent Scraper Jobs:");
        $this->table(
            ['Job ID', 'Status', 'Created', 'Updated', 'Errors'],
            $recentJobs->map(function ($job) {
                return [
                    substr($job->uuid, 0, 8) . '...',
                    $this->getStatusBadge($job->status->value),
                    number_format($job->create_count),
                    number_format($job->update_count),
                    number_format($job->error_count),
                ];
            })->toArray()
        );

        // Data Quality Metrics
        $geoMatched = Project::where('external_source', 'dime')
            ->whereNotNull('region_id')
            ->whereNotNull('province_id')
            ->whereNotNull('city_id')
            ->count();

        $geoMatchRate = $totalProjects > 0 ? round(($geoMatched / $totalProjects) * 100, 2) : 0;

        $this->info("Data Quality:");
        $this->line("  Geographic Match Rate: {$geoMatchRate}%");
        $this->line("  Fully Geo-Referenced: " . number_format($geoMatched) . " projects");
    }

    protected function getStatusBadge(string $status): string
    {
        return match ($status) {
            'running' => '<fg=yellow>● RUNNING</>',
            'completed' => '<fg=green>✓ COMPLETED</>',
            'failed' => '<fg=red>✗ FAILED</>',
            'paused' => '<fg=cyan>⏸ PAUSED</>',
            default => $status,
        };
    }

    protected function getDuration(ScraperJob $job): string
    {
        $end = $job->completed_at ?? now();
        $duration = $job->created_at->diff($end);

        if ($duration->days > 0) {
            return $duration->format('%dd %hh %im');
        } elseif ($duration->h > 0) {
            return $duration->format('%hh %im %ss');
        } else {
            return $duration->format('%im %ss');
        }
    }

    protected function getProcessingRate(ScraperJob $job): float
    {
        $duration = $job->created_at->diffInMinutes(now());
        $processed = ($job->create_count + $job->update_count);

        return $duration > 0 ? round($processed / $duration, 2) : 0;
    }

    protected function renderProgressBar(float $percentage): void
    {
        $barLength = 50;
        $filled = (int) ($barLength * $percentage / 100);
        $empty = $barLength - $filled;

        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);

        $this->line("[{$bar}] {$percentage}%");
    }
}