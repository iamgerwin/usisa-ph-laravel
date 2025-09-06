<?php

namespace App\Enums;

enum ScraperJobStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::PAUSED => 'Paused',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get the color for UI display
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::PAUSED => 'warning',
            self::CANCELLED => 'secondary',
        };
    }

    /**
     * Check if the job can be resumed
     */
    public function canResume(): bool
    {
        return in_array($this, [self::PAUSED, self::FAILED]);
    }

    /**
     * Check if the job can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::RUNNING, self::PAUSED]);
    }

    /**
     * Check if the job is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if the job is active
     */
    public function isActive(): bool
    {
        return $this === self::RUNNING;
    }

    /**
     * Get all values for form selects
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}