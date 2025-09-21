<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CheckDimeStatusCommand extends Command
{
    protected $signature = 'dime:check-status
                            {--notify : Send notification when site is back online}
                            {--wait : Keep checking until site is online}
                            {--interval=300 : Check interval in seconds when using --wait}';

    protected $description = 'Check if DIME.gov.ph is online and accessible';

    protected array $endpoints = [
        'Main Site' => 'https://www.dime.gov.ph',
        'API Projects' => 'https://www.dime.gov.ph/api/projects',
        'API v1' => 'https://www.dime.gov.ph/api/v1/projects',
        'GraphQL' => 'https://www.dime.gov.ph/graphql',
        'Sitemap' => 'https://www.dime.gov.ph/sitemap.xml',
    ];

    public function handle()
    {
        $wait = $this->option('wait');
        $interval = (int) $this->option('interval');
        $notify = $this->option('notify');

        do {
            $this->checkAllEndpoints();

            if ($wait) {
                $allOnline = $this->areAllEndpointsOnline();

                if ($allOnline) {
                    $this->info('✅ DIME is back online! Ready for scraping.');

                    if ($notify) {
                        $this->sendNotification();
                    }

                    // Automatically start scraping if confirmed
                    if ($this->confirm('Would you like to start scraping now?')) {
                        $this->call('dime:scrape-projects', [
                            '--limit' => 20000,
                            '--chunk' => 100,
                            '--delay' => 1,
                        ]);
                    }
                    break;
                }

                $this->warn("⏳ Site still down. Checking again in {$interval} seconds...");
                sleep($interval);
            }
        } while ($wait);

        return Command::SUCCESS;
    }

    protected function checkAllEndpoints(): void
    {
        $this->info('=== DIME.gov.ph Status Check ===');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        $results = [];
        $onlineCount = 0;

        foreach ($this->endpoints as $name => $url) {
            $status = $this->checkEndpoint($url);
            $results[] = [
                'Endpoint' => $name,
                'URL' => $url,
                'Status' => $status['badge'],
                'Response' => $status['message'],
            ];

            if ($status['online']) {
                $onlineCount++;
            }

            // Cache the status
            Cache::put("dime_status_{$name}", $status, 300);
        }

        $this->table(['Endpoint', 'URL', 'Status', 'Response'], $results);

        $this->newLine();
        $this->info("Summary: {$onlineCount}/" . count($this->endpoints) . " endpoints online");

        // Check last successful scrape
        $this->showLastScrapeInfo();
    }

    protected function checkEndpoint(string $url): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; USISA-Monitor/1.0)',
                ])
                ->get($url);

            $status = $response->status();
            $body = $response->body();

            // Check for maintenance page
            if (str_contains(strtolower($body), 'maintenance') ||
                str_contains($body, '/maintenance')) {
                return [
                    'online' => false,
                    'badge' => '<fg=yellow>🔧 MAINTENANCE</>',
                    'message' => 'Under maintenance',
                ];
            }

            if ($status === 200) {
                // Additional check for API endpoints
                if (str_contains($url, 'api') || str_contains($url, 'graphql')) {
                    try {
                        $json = json_decode($body, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            return [
                                'online' => true,
                                'badge' => '<fg=green>✅ ONLINE</>',
                                'message' => 'API responding with valid JSON',
                            ];
                        }
                    } catch (\Exception $e) {
                        // Not JSON, but might still be valid
                    }
                }

                return [
                    'online' => true,
                    'badge' => '<fg=green>✅ ONLINE</>',
                    'message' => "HTTP {$status} - OK",
                ];
            }

            if ($status === 404) {
                return [
                    'online' => false,
                    'badge' => '<fg=red>❌ NOT FOUND</>',
                    'message' => 'Endpoint does not exist',
                ];
            }

            if ($status === 503) {
                return [
                    'online' => false,
                    'badge' => '<fg=yellow>⚠️ UNAVAILABLE</>',
                    'message' => 'Service unavailable',
                ];
            }

            return [
                'online' => false,
                'badge' => '<fg=red>❌ ERROR</>',
                'message' => "HTTP {$status}",
            ];

        } catch (\Exception $e) {
            return [
                'online' => false,
                'badge' => '<fg=red>❌ TIMEOUT</>',
                'message' => 'Connection timeout or error',
            ];
        }
    }

    protected function areAllEndpointsOnline(): bool
    {
        foreach ($this->endpoints as $name => $url) {
            $cached = Cache::get("dime_status_{$name}");
            if (!$cached || !$cached['online']) {
                return false;
            }
        }
        return true;
    }

    protected function showLastScrapeInfo(): void
    {
        $lastJob = \App\Models\ScraperJob::whereHas('source', function ($q) {
            $q->where('code', 'dime');
        })
            ->latest()
            ->first();

        if ($lastJob) {
            $this->info('Last Scrape Job:');
            $this->line("  - Job ID: {$lastJob->uuid}");
            $this->line("  - Status: {$lastJob->status->value}");
            $this->line("  - Started: {$lastJob->created_at->diffForHumans()}");
            $this->line("  - Projects: {$lastJob->create_count} created, {$lastJob->update_count} updated");
        } else {
            $this->info('No previous scrape jobs found.');
        }
    }

    protected function sendNotification(): void
    {
        // You can implement email/slack notification here
        $this->info('📧 Notification would be sent (not implemented)');
    }
}