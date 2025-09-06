<?php

namespace App\Services\Scrapers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\Carbon;

class DimeScraperStrategy extends BaseScraperStrategy
{
    /**
     * Process DIME.gov.ph API data into project model format
     */
    public function processData(array $rawData): array
    {
        $mapped = $this->mapFields($rawData);
        
        return [
            'source_id' => $rawData['id'] ?? null,
            'source_type' => 'dime',
            'title' => $this->cleanText($mapped['title'] ?? $rawData['project_name'] ?? ''),
            'description' => $this->cleanText($mapped['description'] ?? $rawData['project_description'] ?? ''),
            'status' => $this->mapStatus($mapped['status'] ?? $rawData['status'] ?? 'active'),
            'department_id' => $this->resolveDepartmentId($mapped['department'] ?? $rawData['implementing_agency'] ?? null),
            'category_id' => $this->resolveCategoryId($mapped['category'] ?? $rawData['project_type'] ?? null),
            'cost' => $this->parseAmount($mapped['cost'] ?? $rawData['approved_budget'] ?? 0),
            'start_date' => $this->parseDate($mapped['start_date'] ?? $rawData['start_date'] ?? null),
            'end_date' => $this->parseDate($mapped['end_date'] ?? $rawData['end_date'] ?? null),
            'location' => $mapped['location'] ?? $rawData['location'] ?? null,
            'latitude' => $this->parseCoordinate($mapped['latitude'] ?? $rawData['lat'] ?? null),
            'longitude' => $this->parseCoordinate($mapped['longitude'] ?? $rawData['lng'] ?? null),
            'contractor' => $mapped['contractor'] ?? $rawData['contractor_name'] ?? null,
            'contract_amount' => $this->parseAmount($mapped['contract_amount'] ?? $rawData['contract_amount'] ?? null),
            'progress_percentage' => $this->parsePercentage($mapped['progress'] ?? $rawData['physical_progress'] ?? 0),
            'beneficiaries_count' => $this->parseInt($mapped['beneficiaries'] ?? $rawData['beneficiaries'] ?? null),
            'metadata' => $this->buildMetadata($rawData),
            'scraped_at' => now(),
        ];
    }

    /**
     * Validate DIME API response data
     */
    public function validateData(array $data): bool
    {
        return isset($data['id']) || isset($data['project_name']);
    }

    /**
     * Get the model class for projects
     */
    public function getModelClass(): string
    {
        return Project::class;
    }

    /**
     * Get unique field for duplicate detection
     */
    public function getUniqueField(): string
    {
        return 'source_id';
    }

    /**
     * Map DIME status to our ProjectStatus enum
     */
    protected function mapStatus(string $status): ProjectStatus
    {
        $status = strtolower($status);
        
        return match($status) {
            'ongoing', 'in_progress', 'active' => ProjectStatus::ACTIVE,
            'completed', 'finished' => ProjectStatus::COMPLETED,
            'pending', 'planned' => ProjectStatus::PENDING,
            'suspended', 'on_hold' => ProjectStatus::ON_HOLD,
            'cancelled', 'terminated' => ProjectStatus::CANCELLED,
            default => ProjectStatus::DRAFT,
        };
    }

    /**
     * Clean text data
     */
    protected function cleanText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }
        
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Parse amount values
     */
    protected function parseAmount($value): ?float
    {
        if (!$value) {
            return null;
        }
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        $value = preg_replace('/[^0-9.-]/', '', $value);
        return $value ? (float) $value : null;
    }

    /**
     * Parse date values
     */
    protected function parseDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse coordinate values
     */
    protected function parseCoordinate($value): ?float
    {
        if (!$value || !is_numeric($value)) {
            return null;
        }
        
        $coord = (float) $value;
        
        if ($coord < -180 || $coord > 180) {
            return null;
        }
        
        return $coord;
    }

    /**
     * Parse percentage values
     */
    protected function parsePercentage($value): float
    {
        if (!$value) {
            return 0;
        }
        
        if (is_numeric($value)) {
            $percent = (float) $value;
            
            if ($percent > 1 && $percent <= 100) {
                return $percent;
            }
            
            if ($percent >= 0 && $percent <= 1) {
                return $percent * 100;
            }
        }
        
        return 0;
    }

    /**
     * Parse integer values
     */
    protected function parseInt($value): ?int
    {
        if (!$value) {
            return null;
        }
        
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        $value = preg_replace('/[^0-9]/', '', $value);
        return $value ? (int) $value : null;
    }

    /**
     * Resolve department ID from name
     */
    protected function resolveDepartmentId($departmentName): ?int
    {
        if (!$departmentName) {
            return null;
        }
        
        $department = \App\Models\Department::firstOrCreate(
            ['name' => $departmentName],
            [
                'code' => $this->generateCode($departmentName),
                'description' => "Auto-created from DIME scraper",
            ]
        );
        
        return $department->id;
    }

    /**
     * Resolve category ID from name
     */
    protected function resolveCategoryId($categoryName): ?int
    {
        if (!$categoryName) {
            return null;
        }
        
        $category = \App\Models\Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'slug' => \Illuminate\Support\Str::slug($categoryName),
                'description' => "Auto-created from DIME scraper",
            ]
        );
        
        return $category->id;
    }

    /**
     * Generate code from name
     */
    protected function generateCode(string $name): string
    {
        $words = explode(' ', strtoupper($name));
        $code = '';
        
        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $code .= substr($word, 0, 1);
            }
        }
        
        if (strlen($code) < 3) {
            $code = strtoupper(substr(str_replace(' ', '', $name), 0, 3));
        }
        
        return $code;
    }

    /**
     * Build metadata array from raw data
     */
    protected function buildMetadata(array $rawData): array
    {
        $metadata = [
            'source' => 'dime',
            'scraped_at' => now()->toIso8601String(),
            'raw_status' => $rawData['status'] ?? null,
        ];
        
        $extraFields = [
            'project_code',
            'procurement_mode',
            'fund_source',
            'project_classification',
            'remarks',
            'date_awarded',
            'notice_to_proceed',
            'target_completion',
            'revised_completion',
            'actual_completion',
        ];
        
        foreach ($extraFields as $field) {
            if (isset($rawData[$field])) {
                $metadata[$field] = $rawData[$field];
            }
        }
        
        return $metadata;
    }
}