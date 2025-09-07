<?php

namespace App\Services\Scrapers;

use App\Contracts\ScraperStrategy;
use App\Models\ScraperJob;
use App\Models\ScraperSource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class BaseScraperStrategy implements ScraperStrategy
{
    protected Client $httpClient;
    protected ScraperSource $source;
    protected ?ScraperJob $job = null;
    protected array $errors = [];
    protected int $retryAttempts = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function __construct(ScraperSource $source)
    {
        $this->source = $source;
        $this->retryAttempts = $source->retry_attempts ?? 3;
        $this->initializeHttpClient();
    }

    protected function initializeHttpClient(): void
    {
        $config = $this->getHttpConfig();
        $this->httpClient = new Client($config);
    }

    public function getHttpConfig(): array
    {
        $config = [
            'base_uri' => $this->source->base_url,
            'timeout' => $this->source->timeout ?? 30,
            'verify' => true,
            'http_errors' => false,
        ];

        if ($this->source->headers) {
            $config['headers'] = $this->source->headers;
        }

        return $config;
    }

    public function scrapeItem(int $id): ?array
    {
        $url = $this->source->buildUrl($id);
        
        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                $response = $this->httpClient->get($url);
                
                if ($response->getStatusCode() === 200) {
                    $content = $response->getBody()->getContents();
                    $rawData = $this->extractData($content);
                    
                    if ($rawData && $this->validateData($rawData)) {
                        return $this->processData($rawData);
                    }
                    
                    $this->logError($id, 'Invalid data structure received');
                    return null;
                }
                
                if ($response->getStatusCode() === 429) {
                    $this->handleRateLimit();
                    continue;
                }
                
                if ($response->getStatusCode() === 404) {
                    return null;
                }
                
                $this->logError($id, "HTTP {$response->getStatusCode()}: {$response->getReasonPhrase()}");
                
            } catch (RequestException $e) {
                $this->logError($id, "Request failed: {$e->getMessage()}");
                
                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000 * $attempt);
                    continue;
                }
            } catch (\Exception $e) {
                $this->logError($id, "Unexpected error: {$e->getMessage()}");
                return null;
            }
        }
        
        return null;
    }

    public function scrapeBatch(array $ids): Collection
    {
        $results = collect();
        
        foreach ($ids as $id) {
            $data = $this->scrapeItem($id);
            
            if ($data !== null) {
                $results->push($data);
            }
            
            if ($this->source->rate_limit > 0) {
                usleep((1000000 / $this->source->rate_limit));
            }
        }
        
        return $results;
    }

    public function handleRateLimit(): void
    {
        $delay = 60; // Default 60 seconds
        
        if ($this->source->metadata && isset($this->source->metadata['rate_limit_delay'])) {
            $delay = $this->source->metadata['rate_limit_delay'];
        }
        
        Log::warning("Rate limit hit for {$this->source->name}, waiting {$delay} seconds");
        sleep($delay);
    }

    public function setJob(ScraperJob $job): void
    {
        $this->job = $job;
    }

    protected function logError(int $id, string $message): void
    {
        $this->errors[] = [
            'id' => $id,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];
        
        if ($this->job) {
            $this->job->logError($id, $message);
        }
        
        Log::error("Scraper error for {$this->source->name} ID {$id}: {$message}");
    }

    protected function mapFields(array $data): array
    {
        if (!$this->source->field_mapping) {
            return $data;
        }
        
        $mapped = [];
        
        foreach ($this->source->field_mapping as $targetField => $sourceField) {
            $value = data_get($data, $sourceField);
            data_set($mapped, $targetField, $value);
        }
        
        return $mapped;
    }

    /**
     * Extract data from response content
     * Can be overridden by specific strategies
     */
    protected function extractData(string $content): ?array
    {
        // Try to parse as JSON first
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        // If not JSON, return null (strategies can override this)
        return null;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function clearErrors(): void
    {
        $this->errors = [];
    }
}