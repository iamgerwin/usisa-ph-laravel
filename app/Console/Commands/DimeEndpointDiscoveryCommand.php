<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DimeEndpointDiscoveryCommand extends Command
{
    protected $signature = 'dime:discover-endpoints
                            {--test-all : Test all possible endpoint combinations}
                            {--save : Save working endpoints to configuration}';

    protected $description = 'Discover and test alternative DIME API endpoints';

    protected array $baseUrls = [
        'https://www.dime.gov.ph',
        'https://api.dime.gov.ph',
        'https://data.dime.gov.ph',
        'https://dime.gov.ph',
    ];

    protected array $paths = [
        '/api/projects',
        '/api/v1/projects',
        '/api/v2/projects',
        '/projects',
        '/data/projects',
        '/public/api/projects',
        '/_api/projects',
        '/rest/projects',
        '/graphql',
        '/api/graphql',
        '/search/projects',
        '/api/search',
    ];

    protected array $workingEndpoints = [];

    public function handle()
    {
        $this->info('🔍 Discovering DIME API endpoints...');
        $testAll = $this->option('test-all');

        $progressBar = null;
        if ($testAll) {
            $total = count($this->baseUrls) * count($this->paths);
            $progressBar = $this->output->createProgressBar($total);
            $progressBar->start();
        }

        foreach ($this->baseUrls as $baseUrl) {
            $this->line("\nTesting base URL: {$baseUrl}");

            // First check if base URL is accessible
            if (!$this->testUrl($baseUrl)) {
                $this->warn("  Base URL not accessible");
                if ($testAll && $progressBar) {
                    $progressBar->advance(count($this->paths));
                }
                continue;
            }

            foreach ($this->paths as $path) {
                $fullUrl = $baseUrl . $path;

                if ($progressBar) {
                    $progressBar->advance();
                }

                $result = $this->testEndpoint($fullUrl);

                if ($result['success']) {
                    $this->workingEndpoints[] = [
                        'url' => $fullUrl,
                        'type' => $result['type'],
                        'response_time' => $result['response_time'],
                    ];

                    $this->newLine();
                    $this->info("✅ Found working endpoint: {$fullUrl}");
                    $this->line("   Type: {$result['type']}");
                    $this->line("   Response time: {$result['response_time']}ms");

                    if (!$testAll) {
                        break 2; // Found one working endpoint
                    }
                }
            }
        }

        if ($progressBar) {
            $progressBar->finish();
            $this->newLine(2);
        }

        $this->displayResults();

        if ($this->option('save') && !empty($this->workingEndpoints)) {
            $this->saveEndpoints();
        }

        return Command::SUCCESS;
    }

    protected function testUrl(string $url): bool
    {
        try {
            $response = Http::timeout(5)->head($url);
            return $response->status() !== 404;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function testEndpoint(string $url): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'USISA-Scraper/1.0',
                ])
                ->get($url, ['limit' => 1]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->status() === 503 ||
                str_contains($response->body(), 'maintenance')) {
                return [
                    'success' => false,
                    'type' => 'maintenance',
                    'response_time' => $responseTime,
                ];
            }

            if ($response->successful()) {
                $body = $response->body();
                $type = $this->detectResponseType($body);

                if ($type !== 'unknown') {
                    return [
                        'success' => true,
                        'type' => $type,
                        'response_time' => $responseTime,
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::debug("Endpoint test failed: {$url}", ['error' => $e->getMessage()]);
        }

        return [
            'success' => false,
            'type' => 'error',
            'response_time' => 0,
        ];
    }

    protected function detectResponseType(string $body): string
    {
        // Check for JSON
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($json['data']) || isset($json['projects'])) {
                return 'json_api';
            }
            if (isset($json['data']['projects'])) {
                return 'graphql';
            }
        }

        // Check for Next.js
        if (str_contains($body, '__NEXT_DATA__')) {
            return 'nextjs';
        }

        // Check for HTML with project data
        if (str_contains($body, 'project') && str_contains($body, '<')) {
            return 'html';
        }

        // Check for XML
        if (str_contains($body, '<?xml')) {
            return 'xml';
        }

        return 'unknown';
    }

    protected function displayResults(): void
    {
        $this->newLine();
        $this->info('=== Discovery Results ===');

        if (empty($this->workingEndpoints)) {
            $this->error('No working endpoints found');
            $this->line('DIME appears to be under maintenance or inaccessible.');
        } else {
            $this->info('Found ' . count($this->workingEndpoints) . ' working endpoint(s):');

            $this->table(
                ['URL', 'Type', 'Response Time'],
                array_map(function ($endpoint) {
                    return [
                        $endpoint['url'],
                        $endpoint['type'],
                        $endpoint['response_time'] . 'ms',
                    ];
                }, $this->workingEndpoints)
            );
        }
    }

    protected function saveEndpoints(): void
    {
        $configPath = config_path('dime-endpoints.php');

        $config = "<?php\n\nreturn [\n    'endpoints' => [\n";
        foreach ($this->workingEndpoints as $endpoint) {
            $config .= "        [\n";
            $config .= "            'url' => '{$endpoint['url']}',\n";
            $config .= "            'type' => '{$endpoint['type']}',\n";
            $config .= "            'response_time' => {$endpoint['response_time']},\n";
            $config .= "        ],\n";
        }
        $config .= "    ],\n";
        $config .= "    'discovered_at' => '" . now()->toIso8601String() . "',\n";
        $config .= "];\n";

        file_put_contents($configPath, $config);
        $this->info("Configuration saved to: {$configPath}");
    }
}