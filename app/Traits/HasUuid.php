<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait
     * 
     * Uses orderedUuid() to generate time-ordered UUIDs (similar to UUID v7)
     * which provide better database performance and natural chronological sorting
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                // Use orderedUuid for time-based ordering (UUID v7-like behavior)
                $model->uuid = (string) Str::orderedUuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     * This makes the model use UUID for route model binding by default
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Find a model by its UUID
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a model by its UUID or fail
     */
    public static function findByUuidOrFail(string $uuid): static
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Scope to find by UUID
     */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}