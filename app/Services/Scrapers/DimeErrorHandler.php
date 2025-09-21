<?php

namespace App\Services\Scrapers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DimeErrorHandler
{
    protected array $errorLog = [];
    protected array $recoveryStrategies = [];
    protected int $maxRetries = 3;

    public function __construct()
    {
        $this->initializeRecoveryStrategies();
    }

    /**
     * Handle scraper errors with recovery strategies
     */
    public function handleError(\Exception $exception, array $context = []): bool
    {
        $errorType = $this->classifyError($exception);
        $this->logError($errorType, $exception->getMessage(), $context);

        // Try recovery strategies
        foreach ($this->recoveryStrategies[$errorType] ?? [] as $strategy) {
            if ($this->executeStrategy($strategy, $context)) {
                Log::info('DIME error recovered', [
                    'error_type' => $errorType,
                    'strategy' => $strategy,
                    'context' => array_slice($context, 0, 3)
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Classify error types
     */
    protected function classifyError(\Exception $exception): string
    {
        $message = $exception->getMessage();
        $code = method_exists($exception, 'getCode') ? $exception->getCode() : 0;

        // Network errors
        if (stripos($message, 'timeout') !== false ||
            stripos($message, 'connection') !== false) {
            return 'network';
        }

        // API errors
        if ($code >= 400 && $code < 500) {
            if ($code === 404) return 'not_found';
            if ($code === 429) return 'rate_limit';
            if ($code === 403) return 'forbidden';
            return 'client_error';
        }

        if ($code >= 500) {
            if ($code === 503) return 'maintenance';
            return 'server_error';
        }

        // Database errors
        if (stripos($message, 'SQLSTATE') !== false) {
            if (stripos($message, 'not null') !== false) return 'null_constraint';
            if (stripos($message, 'duplicate') !== false) return 'duplicate';
            if (stripos($message, 'foreign key') !== false) return 'foreign_key';
            return 'database';
        }

        // Data validation errors
        if (stripos($message, 'invalid') !== false ||
            stripos($message, 'validation') !== false) {
            return 'validation';
        }

        return 'unknown';
    }

    /**
     * Initialize recovery strategies
     */
    protected function initializeRecoveryStrategies(): void
    {
        $this->recoveryStrategies = [
            'network' => [
                'wait_and_retry',
                'use_cached_data',
                'switch_endpoint',
            ],
            'maintenance' => [
                'wait_for_online',
                'use_alternative_source',
                'notify_admin',
            ],
            'not_found' => [
                'check_alternative_endpoints',
                'use_web_scraping',
                'skip_item',
            ],
            'rate_limit' => [
                'exponential_backoff',
                'reduce_batch_size',
                'switch_api_key',
            ],
            'null_constraint' => [
                'provide_defaults',
                'generate_placeholder',
                'mark_incomplete',
            ],
            'duplicate' => [
                'update_existing',
                'skip_item',
                'merge_data',
            ],
            'validation' => [
                'clean_data',
                'apply_transformations',
                'use_fallback_values',
            ],
        ];
    }

    /**
     * Execute recovery strategy
     */
    protected function executeStrategy(string $strategy, array $context): bool
    {
        return match($strategy) {
            'wait_and_retry' => $this->waitAndRetry($context),
            'use_cached_data' => $this->useCachedData($context),
            'switch_endpoint' => $this->switchEndpoint($context),
            'wait_for_online' => $this->waitForOnline($context),
            'use_alternative_source' => $this->useAlternativeSource($context),
            'notify_admin' => $this->notifyAdmin($context),
            'exponential_backoff' => $this->exponentialBackoff($context),
            'reduce_batch_size' => $this->reduceBatchSize($context),
            'provide_defaults' => $this->provideDefaults($context),
            'generate_placeholder' => $this->generatePlaceholder($context),
            'update_existing' => $this->updateExisting($context),
            'skip_item' => true, // Always succeeds by skipping
            'clean_data' => $this->cleanData($context),
            'apply_transformations' => $this->applyTransformations($context),
            default => false,
        };
    }

    /**
     * Wait and retry strategy
     */
    protected function waitAndRetry(array $context): bool
    {
        $attempt = $context['attempt'] ?? 1;
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        $waitTime = min($attempt * 5, 30); // Max 30 seconds
        sleep($waitTime);
        return true;
    }

    /**
     * Use cached data if available
     */
    protected function useCachedData(array $context): bool
    {
        $cacheKey = 'dime_project_' . ($context['project_id'] ?? 'unknown');
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            Log::info('Using cached data for DIME project', [
                'project_id' => $context['project_id'] ?? 'unknown'
            ]);
            return true;
        }

        return false;
    }

    /**
     * Switch to alternative endpoint
     */
    protected function switchEndpoint(array $context): bool
    {
        $alternativeEndpoints = [
            'https://api.dime.gov.ph/projects',
            'https://www.dime.gov.ph/api/v2/projects',
            'https://data.dime.gov.ph/projects',
        ];

        foreach ($alternativeEndpoints as $endpoint) {
            if ($this->testEndpoint($endpoint)) {
                Cache::put('dime_active_endpoint', $endpoint, 3600);
                return true;
            }
        }

        return false;
    }

    /**
     * Test if endpoint is accessible
     */
    protected function testEndpoint(string $url): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Wait for site to come online
     */
    protected function waitForOnline(array $context): bool
    {
        $maxWait = 300; // 5 minutes
        $checkInterval = 30; // 30 seconds
        $waited = 0;

        while ($waited < $maxWait) {
            if ($this->testEndpoint('https://www.dime.gov.ph')) {
                return true;
            }
            sleep($checkInterval);
            $waited += $checkInterval;
        }

        return false;
    }

    /**
     * Use alternative data source
     */
    protected function useAlternativeSource(array $context): bool
    {
        // This would implement fetching from alternative sources
        // like cached files, backup APIs, or partner APIs
        return false;
    }

    /**
     * Notify administrator
     */
    protected function notifyAdmin(array $context): bool
    {
        Log::critical('DIME scraper requires admin attention', $context);
        // Here you would send email/slack notification
        return false;
    }

    /**
     * Exponential backoff strategy
     */
    protected function exponentialBackoff(array $context): bool
    {
        $attempt = $context['attempt'] ?? 1;
        $waitTime = pow(2, $attempt) * 1000; // milliseconds
        usleep($waitTime * 1000); // Convert to microseconds
        return true;
    }

    /**
     * Reduce batch size for next attempt
     */
    protected function reduceBatchSize(array $context): bool
    {
        $currentBatch = $context['batch_size'] ?? 100;
        $newBatch = max(10, intval($currentBatch / 2));
        Cache::put('dime_batch_size', $newBatch, 3600);
        return true;
    }

    /**
     * Provide default values for null fields
     */
    protected function provideDefaults(array &$context): bool
    {
        $defaults = [
            'project_name' => 'DIME Project ' . uniqid(),
            'status' => 'Unknown',
            'cost' => 0,
            'latitude' => null,
            'longitude' => null,
        ];

        if (isset($context['data'])) {
            foreach ($defaults as $field => $value) {
                if (empty($context['data'][$field])) {
                    $context['data'][$field] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Generate placeholder data
     */
    protected function generatePlaceholder(array &$context): bool
    {
        if (isset($context['data']) && empty($context['data']['project_name'])) {
            $context['data']['project_name'] = 'Placeholder: ' . ($context['data']['project_code'] ?? uniqid());
            $context['data']['is_placeholder'] = true;
            return true;
        }
        return false;
    }

    /**
     * Update existing record instead of creating new
     */
    protected function updateExisting(array $context): bool
    {
        // This would be implemented in the main scraper
        return isset($context['existing_id']);
    }

    /**
     * Clean problematic data
     */
    protected function cleanData(array &$context): bool
    {
        if (!isset($context['data'])) {
            return false;
        }

        // Remove null bytes
        array_walk_recursive($context['data'], function(&$value) {
            if (is_string($value)) {
                $value = str_replace("\0", "", $value);
                $value = trim($value);
            }
        });

        return true;
    }

    /**
     * Apply data transformations
     */
    protected function applyTransformations(array &$context): bool
    {
        if (!isset($context['data'])) {
            return false;
        }

        // Fix common date issues
        if (isset($context['data']['date_started']) &&
            !preg_match('/^\d{4}-\d{2}-\d{2}/', $context['data']['date_started'])) {
            $context['data']['date_started'] = null;
        }

        // Fix coordinates
        if (isset($context['data']['latitude'])) {
            $lat = filter_var($context['data']['latitude'], FILTER_VALIDATE_FLOAT);
            $context['data']['latitude'] = ($lat !== false && $lat >= -90 && $lat <= 90) ? $lat : null;
        }

        if (isset($context['data']['longitude'])) {
            $lon = filter_var($context['data']['longitude'], FILTER_VALIDATE_FLOAT);
            $context['data']['longitude'] = ($lon !== false && $lon >= -180 && $lon <= 180) ? $lon : null;
        }

        return true;
    }

    /**
     * Log error for analysis
     */
    protected function logError(string $type, string $message, array $context): void
    {
        $this->errorLog[] = [
            'type' => $type,
            'message' => $message,
            'context' => array_slice($context, 0, 3),
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep only last 100 errors
        $this->errorLog = array_slice($this->errorLog, -100);

        // Persist to cache for dashboard
        Cache::put('dime_error_log', $this->errorLog, 86400);

        Log::error('DIME scraper error', [
            'type' => $type,
            'message' => $message,
            'project_id' => $context['project_id'] ?? 'unknown',
        ]);
    }

    /**
     * Get error statistics
     */
    public function getErrorStats(): array
    {
        $stats = [];
        foreach ($this->errorLog as $error) {
            $type = $error['type'];
            if (!isset($stats[$type])) {
                $stats[$type] = 0;
            }
            $stats[$type]++;
        }
        return $stats;
    }

    /**
     * Clear error log
     */
    public function clearErrorLog(): void
    {
        $this->errorLog = [];
        Cache::forget('dime_error_log');
    }
}