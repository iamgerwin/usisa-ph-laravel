<?php

namespace App\Models;

use App\Enums\ScraperJobStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScraperSource extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'code',
        'name',
        'base_url',
        'endpoint_pattern',
        'is_active',
        'rate_limit',
        'timeout',
        'retry_attempts',
        'headers',
        'field_mapping',
        'metadata',
        'scraper_class',
        'version',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
        'timeout' => 'integer',
        'retry_attempts' => 'integer',
        'headers' => 'array',
        'field_mapping' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the jobs for this scraper source
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(ScraperJob::class, 'source_id');
    }

    /**
     * Get the latest job for this source
     */
    public function latestJob()
    {
        return $this->hasOne(ScraperJob::class, 'source_id')->latest();
    }

    /**
     * Get running jobs for this source
     */
    public function runningJobs()
    {
        return $this->hasMany(ScraperJob::class, 'source_id')->where('status', 'running');
    }

    /**
     * Scope to get only active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Build the full URL for a given ID
     */
    public function buildUrl(int $id): string
    {
        if ($this->endpoint_pattern) {
            $endpoint = str_replace('{id}', $id, $this->endpoint_pattern);
            return rtrim($this->base_url, '/') . '/' . ltrim($endpoint, '/');
        }
        
        return $this->base_url;
    }

    /**
     * Get the strategy class instance
     */
    public function getStrategyInstance()
    {
        if (!$this->scraper_class || !class_exists($this->scraper_class)) {
            return null;
        }

        return new $this->scraper_class($this);
    }

    /**
     * Check if source has a running job
     */
    public function hasRunningJob(): bool
    {
        return $this->runningJobs()->exists();
    }

    /**
     * Get statistics for this source
     */
    public function getStatistics(): array
    {
        $jobs = $this->jobs();
        
        return [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->where('status', 'completed')->count(),
            'failed_jobs' => $jobs->where('status', 'failed')->count(),
            'total_scraped' => $jobs->sum('success_count'),
            'total_errors' => $jobs->sum('error_count'),
            'total_created' => $jobs->sum('create_count'),
            'total_updated' => $jobs->sum('update_count'),
        ];
    }
}