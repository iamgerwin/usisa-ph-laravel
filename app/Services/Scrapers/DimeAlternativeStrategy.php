<?php

namespace App\Services\Scrapers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class DimeAlternativeStrategy
{
    /**
     * Alternative endpoints and methods to try
     */
    protected array $strategies = [
        'sitemap' => 'https://www.dime.gov.ph/sitemap.xml',
        'rss' => 'https://www.dime.gov.ph/rss',
        'search' => 'https://www.dime.gov.ph/search',
        'graphql' => 'https://www.dime.gov.ph/graphql',
    ];

    /**
     * Try to fetch project data using alternative methods
     */
    public function fetchProjects(int $limit = 100, int $offset = 0): array
    {
        // Try sitemap first
        $projects = $this->fetchFromSitemap($limit, $offset);
        if (!empty($projects)) {
            return $projects;
        }

        // Try search endpoint
        $projects = $this->fetchFromSearch($limit, $offset);
        if (!empty($projects)) {
            return $projects;
        }

        // Try GraphQL if available
        $projects = $this->fetchFromGraphQL($limit, $offset);
        if (!empty($projects)) {
            return $projects;
        }

        // Try direct page scraping
        return $this->fetchFromPages($limit, $offset);
    }

    /**
     * Fetch project URLs from sitemap
     */
    protected function fetchFromSitemap(int $limit, int $offset): array
    {
        try {
            $response = Http::timeout(30)->get($this->strategies['sitemap']);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                $projects = [];

                foreach ($xml->url as $url) {
                    $loc = (string) $url->loc;
                    if (str_contains($loc, '/projects/')) {
                        // Extract project ID from URL
                        if (preg_match('/\/projects\/([a-zA-Z0-9-]+)/', $loc, $matches)) {
                            $projectId = $matches[1];
                            $projects[] = $this->fetchSingleProject($projectId);

                            if (count($projects) >= $limit) {
                                break;
                            }
                        }
                    }
                }

                return array_filter($projects);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch from sitemap: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Fetch from search/filter endpoint
     */
    protected function fetchFromSearch(int $limit, int $offset): array
    {
        try {
            $response = Http::timeout(30)
                ->asForm()
                ->post($this->strategies['search'], [
                    'query' => '',
                    'limit' => $limit,
                    'offset' => $offset,
                    'type' => 'projects',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['results'] ?? $data['projects'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch from search: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Try GraphQL endpoint
     */
    protected function fetchFromGraphQL(int $limit, int $offset): array
    {
        try {
            $query = '
                query GetProjects($limit: Int!, $offset: Int!) {
                    projects(limit: $limit, offset: $offset) {
                        id
                        projectName
                        projectCode
                        description
                        cost
                        region
                        province
                        city
                        barangay
                        latitude
                        longitude
                        status
                        dateStarted
                        implementingOffices {
                            id
                            name
                        }
                        contractors {
                            id
                            name
                        }
                    }
                }
            ';

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->strategies['graphql'], [
                    'query' => $query,
                    'variables' => [
                        'limit' => $limit,
                        'offset' => $offset,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['projects'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch from GraphQL: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Direct page scraping
     */
    protected function fetchFromPages(int $limit, int $offset): array
    {
        try {
            $page = floor($offset / $limit) + 1;
            $response = Http::timeout(60)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get('https://www.dime.gov.ph/projects', [
                    'page' => $page,
                ]);

            if ($response->successful()) {
                return $this->parseProjectsFromHtml($response->body());
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch from pages: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Parse projects from HTML response
     */
    protected function parseProjectsFromHtml(string $html): array
    {
        $projects = [];

        // Check for Next.js data
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if (isset($jsonData['props']['pageProps']['projects'])) {
                return $jsonData['props']['pageProps']['projects'];
            }
        }

        // Use DOM crawler as fallback
        try {
            $crawler = new Crawler($html);

            // Look for project cards/items
            $crawler->filter('[class*="project-card"], [class*="project-item"], article')->each(function (Crawler $node) use (&$projects) {
                $project = [];

                // Extract project name
                $nameNode = $node->filter('h2, h3, [class*="title"]')->first();
                if ($nameNode->count()) {
                    $project['projectName'] = trim($nameNode->text());
                }

                // Extract location
                $locationNode = $node->filter('[class*="location"], [class*="address"]')->first();
                if ($locationNode->count()) {
                    $location = trim($locationNode->text());
                    // Parse location into components
                    $this->parseLocation($location, $project);
                }

                // Extract cost
                $costNode = $node->filter('[class*="cost"], [class*="amount"], [class*="price"]')->first();
                if ($costNode->count()) {
                    $project['cost'] = $this->parseCost($costNode->text());
                }

                // Extract status
                $statusNode = $node->filter('[class*="status"], [class*="badge"]')->first();
                if ($statusNode->count()) {
                    $project['status'] = trim($statusNode->text());
                }

                // Extract link for more details
                $linkNode = $node->filter('a')->first();
                if ($linkNode->count()) {
                    $href = $linkNode->attr('href');
                    if (preg_match('/([a-zA-Z0-9-]+)$/', $href, $matches)) {
                        $project['id'] = $matches[1];
                    }
                }

                if (!empty($project)) {
                    $projects[] = $project;
                }
            });
        } catch (\Exception $e) {
            Log::warning('Failed to parse HTML: ' . $e->getMessage());
        }

        return $projects;
    }

    /**
     * Fetch single project details
     */
    protected function fetchSingleProject(string $projectId): ?array
    {
        try {
            $response = Http::timeout(30)
                ->get("https://www.dime.gov.ph/projects/{$projectId}");

            if ($response->successful()) {
                $html = $response->body();

                // Extract Next.js data
                if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                    $jsonData = json_decode($matches[1], true);
                    if (isset($jsonData['props']['pageProps']['project'])) {
                        return $jsonData['props']['pageProps']['project'];
                    }
                }

                // Fallback to HTML parsing
                return $this->parseProjectFromHtml($html, $projectId);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch project {$projectId}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Parse single project from HTML
     */
    protected function parseProjectFromHtml(string $html, string $projectId): array
    {
        $project = ['id' => $projectId];

        try {
            $crawler = new Crawler($html);

            // Extract title
            $title = $crawler->filter('h1, [class*="project-title"]')->first();
            if ($title->count()) {
                $project['projectName'] = trim($title->text());
            }

            // Extract description
            $description = $crawler->filter('[class*="description"], [class*="overview"]')->first();
            if ($description->count()) {
                $project['description'] = trim($description->text());
            }

            // Extract details from info sections
            $crawler->filter('[class*="detail"], [class*="info"]')->each(function (Crawler $node) use (&$project) {
                $text = $node->text();

                if (str_contains($text, 'Region:')) {
                    $project['region'] = $this->extractValue($text, 'Region:');
                }
                if (str_contains($text, 'Province:')) {
                    $project['province'] = $this->extractValue($text, 'Province:');
                }
                if (str_contains($text, 'City:')) {
                    $project['city'] = $this->extractValue($text, 'City:');
                }
                if (str_contains($text, 'Barangay:')) {
                    $project['barangay'] = $this->extractValue($text, 'Barangay:');
                }
                if (str_contains($text, 'Cost:') || str_contains($text, '₱')) {
                    $project['cost'] = $this->parseCost($text);
                }
                if (str_contains($text, 'Status:')) {
                    $project['status'] = $this->extractValue($text, 'Status:');
                }
            });

        } catch (\Exception $e) {
            Log::warning("Failed to parse project HTML: " . $e->getMessage());
        }

        return $project;
    }

    /**
     * Parse location string into components
     */
    protected function parseLocation(string $location, array &$project): void
    {
        // Common patterns: "Barangay, City, Province, Region"
        $parts = array_map('trim', explode(',', $location));
        $count = count($parts);

        if ($count >= 4) {
            $project['barangay'] = $parts[0];
            $project['city'] = $parts[1];
            $project['province'] = $parts[2];
            $project['region'] = $parts[3];
        } elseif ($count == 3) {
            $project['city'] = $parts[0];
            $project['province'] = $parts[1];
            $project['region'] = $parts[2];
        } elseif ($count == 2) {
            $project['province'] = $parts[0];
            $project['region'] = $parts[1];
        } elseif ($count == 1) {
            $project['region'] = $parts[0];
        }
    }

    /**
     * Parse cost from text
     */
    protected function parseCost(string $text): ?float
    {
        // Remove currency symbols and non-numeric characters
        $cleaned = preg_replace('/[^0-9.]/', '', $text);
        return $cleaned ? (float) $cleaned : null;
    }

    /**
     * Extract value after label
     */
    protected function extractValue(string $text, string $label): string
    {
        $parts = explode($label, $text);
        if (count($parts) > 1) {
            return trim($parts[1]);
        }
        return '';
    }
}