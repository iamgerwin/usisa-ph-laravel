<?php

namespace App\Models;

use App\Enums\ScraperJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScraperJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'start_id',
        'end_id',
        'current_id',
        'chunk_size',
        'status',
        'started_at',
        'completed_at',
        'success_count',
        'error_count',
        'skip_count',
        'update_count',
        'create_count',
        'stats',
        'errors',
        'notes',
        'triggered_by',
    ];

    protected $casts = [
        'status' => ScraperJobStatus::class,
        'start_id' => 'integer',
        'end_id' => 'integer',
        'current_id' => 'integer',
        'chunk_size' => 'integer',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'skip_count' => 'integer',
        'update_count' => 'integer',
        'create_count' => 'integer',
        'stats' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the source for this job
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ScraperSource::class, 'source_id');
    }

    /**
     * Check if the job can be resumed
     */
    public function canResume(): bool
    {
        return $this->status->canResume();
    }

    /**
     * Check if the job can be cancelled
     */
    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    /**
     * Check if the job is running
     */
    public function isRunning(): bool
    {
        return $this->status === ScraperJobStatus::RUNNING;
    }

    /**
     * Check if the job is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === ScraperJobStatus::COMPLETED;
    }

    /**
     * Check if the job has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === ScraperJobStatus::FAILED;
    }

    /**
     * Mark the job as running
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => ScraperJobStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the job as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => ScraperJobStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the job as failed
     */
    public function markAsFailed(string $error = null): void
    {
        $errors = $this->errors ?? [];
        if ($error) {
            $errors[] = [
                'timestamp' => now()->toIso8601String(),
                'message' => $error,
            ];
        }

        $this->update([
            'status' => ScraperJobStatus::FAILED,
            'errors' => $errors,
        ]);
    }

    /**
     * Mark the job as paused
     */
    public function markAsPaused(): void
    {
        $this->update([
            'status' => ScraperJobStatus::PAUSED,
        ]);
    }

    /**
     * Mark the job as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => ScraperJobStatus::CANCELLED,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $currentId): void
    {
        $this->update(['current_id' => $currentId]);
    }

    /**
     * Increment success count
     */
    public function incrementSuccess(int $count = 1): void
    {
        $this->increment('success_count', $count);
    }

    /**
     * Increment error count
     */
    public function incrementError(int $count = 1): void
    {
        $this->increment('error_count', $count);
    }

    /**
     * Increment skip count
     */
    public function incrementSkip(int $count = 1): void
    {
        $this->increment('skip_count', $count);
    }

    /**
     * Increment update count
     */
    public function incrementUpdate(int $count = 1): void
    {
        $this->increment('update_count', $count);
    }

    /**
     * Increment create count
     */
    public function incrementCreate(int $count = 1): void
    {
        $this->increment('create_count', $count);
    }

    /**
     * Get total processed count
     */
    public function getTotalProcessedAttribute(): int
    {
        return $this->success_count + $this->error_count + $this->skip_count;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        $total = $this->end_id - $this->start_id + 1;
        if ($total <= 0) {
            return 0;
        }

        $processed = $this->current_id ? $this->current_id - $this->start_id + 1 : 0;
        return round(($processed / $total) * 100, 2);
    }

    /**
     * Get remaining count
     */
    public function getRemainingCountAttribute(): int
    {
        if (!$this->current_id) {
            return $this->end_id - $this->start_id + 1;
        }
        return max(0, $this->end_id - $this->current_id);
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): ?string
    {
        $duration = $this->duration;
        if ($duration === null) {
            return null;
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Scope for pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', ScraperJobStatus::PENDING);
    }

    /**
     * Scope for running jobs
     */
    public function scopeRunning($query)
    {
        return $query->where('status', ScraperJobStatus::RUNNING);
    }

    /**
     * Scope for completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', ScraperJobStatus::COMPLETED);
    }

    /**
     * Scope for failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', ScraperJobStatus::FAILED);
    }

    /**
     * Log an error
     */
    public function logError(int $id, string $message, array $context = []): void
    {
        $errors = $this->errors ?? [];
        $errors[] = [
            'id' => $id,
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
            'context' => $context,
        ];
        
        // Keep only last 1000 errors to prevent bloat
        if (count($errors) > 1000) {
            $errors = array_slice($errors, -1000);
        }
        
        $this->update(['errors' => $errors]);
    }

    /**
     * Add statistics
     */
    public function addStatistic(string $key, $value): void
    {
        $stats = $this->stats ?? [];
        $stats[$key] = $value;
        $this->update(['stats' => $stats]);
    }
}