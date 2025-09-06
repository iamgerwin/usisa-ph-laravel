<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get the color for UI display
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'warning',
        };
    }

    /**
     * Check if the content is publicly visible
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
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