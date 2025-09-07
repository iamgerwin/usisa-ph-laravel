<?php

namespace App\Services\Scrapers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DimeScraperStrategy extends BaseScraperStrategy
{
    /**
     * Process DIME.gov.ph API data into project model format
     */
    public function processData(array $rawData): array
    {
        // Handle nested structure from Next.js response
        $project = $rawData['pageProps']['project'] ?? $rawData;
        
        // Process implementing offices, contractors, and source of funds
        $implementingOffices = $this->processImplementingOffices($project['implementingOffices'] ?? []);
        $contractors = $this->processContractors($project['contractors'] ?? []);
        $sourceOfFunds = $this->processSourceOfFunds($project['sourceOfFunds'] ?? []);
        $program = $this->processProgram($project['program'] ?? null);
        
        return [
            'dime_id' => $project['id'] ?? null,
            'project_name' => $this->cleanText($project['projectName'] ?? ''),
            'project_code' => $project['projectCode'] ?? null,
            'description' => $this->cleanText($project['description'] ?? ''),
            'project_image_url' => $project['projectImageUrl'] ?? null,
            'street_address' => $project['streetAddress'] ?? null,
            'city_name' => $project['city'] ?? null,
            'city_code' => $project['cityCode'] ?? null,
            'zip_code' => $project['zipCode'] ?? null,
            'barangay_name' => $project['barangay'] ?? null,
            'barangay_code' => $project['barangayCode'] ?? null,
            'province_name' => $project['province'] ?? null,
            'province_code' => $project['provinceCode'] ?? null,
            'region_name' => $project['region'] ?? null,
            'region_code' => $project['regionCode'] ?? null,
            'country' => $project['country'] ?? 'Philippines',
            'state' => $project['state'] ?? null,
            'latitude' => $this->parseCoordinate($project['latitude'] ?? null),
            'longitude' => $this->parseCoordinate($project['longitude'] ?? null),
            'status' => $project['status'] ?? null,
            'publication_status' => $project['publicationStatus'] ?? 'Published',
            'cost' => $this->parseAmount($project['cost'] ?? 0),
            'utilized_amount' => $this->parseAmount($project['utilizedAmount'] ?? null) ?? 0,
            'date_started' => $this->parseDate($project['dateStarted'] ?? null),
            'actual_date_started' => $this->parseDate($project['actualDateStarted'] ?? null),
            'contract_completion_date' => $this->parseDate($project['contractCompletionDate'] ?? null),
            'actual_contract_completion_date' => $this->parseDate($project['actualContractCompletionDate'] ?? null),
            'as_of_date' => $this->parseDate($project['asOfDate'] ?? null),
            'last_updated_project_cost' => $this->parseDate($project['lastUpdatedProjectCost'] ?? null),
            'updates_count' => $project['updatesCount'] ?? 0,
            'program_id' => $program['id'] ?? null,
            'data_source' => 'dime',
            'last_synced_at' => now(),
            'metadata' => $this->buildMetadata($project, [
                'implementing_offices' => $implementingOffices,
                'contractors' => $contractors,
                'source_of_funds' => $sourceOfFunds,
                'program' => $program,
                'resources' => $project['resources'] ?? [],
                'progresses' => $project['progresses'] ?? [],
                'dime_created_at' => $project['createdAt'] ?? null,
                'dime_updated_at' => $project['updatedAt'] ?? null,
            ]),
        ];
    }

    /**
     * Validate DIME API response data
     */
    public function validateData(array $data): bool
    {
        // Check for Next.js response structure
        if (isset($data['pageProps']['project'])) {
            $project = $data['pageProps']['project'];
            return isset($project['id']) || isset($project['projectName']);
        }
        
        // Check for direct project data
        return isset($data['id']) || isset($data['projectName']);
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
        return 'dime_id';
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
     * Process implementing offices array
     */
    protected function processImplementingOffices(array $offices): array
    {
        return array_map(function ($office) {
            return [
                'id' => $office['id'] ?? null,
                'name' => $office['name'] ?? null,
                'abbreviation' => $office['nameAbbreviation'] ?? null,
                'logo_url' => $office['logoUrl'] ?? null,
            ];
        }, $offices);
    }

    /**
     * Process contractors array
     */
    protected function processContractors(array $contractors): array
    {
        return array_map(function ($contractor) {
            return [
                'id' => $contractor['id'] ?? null,
                'name' => $contractor['name'] ?? null,
                'abbreviation' => $contractor['nameAbbreviation'] ?? null,
                'logo_url' => $contractor['logoUrl'] ?? null,
            ];
        }, $contractors);
    }

    /**
     * Process source of funds array
     */
    protected function processSourceOfFunds(array $sources): array
    {
        return array_map(function ($source) {
            return [
                'id' => $source['id'] ?? null,
                'name' => $source['name'] ?? null,
                'abbreviation' => $source['nameAbbreviation'] ?? null,
                'logo_url' => $source['logoUrl'] ?? null,
            ];
        }, $sources);
    }

    /**
     * Process program data and create if needed
     */
    protected function processProgram(?array $program): ?array
    {
        if (!$program || !isset($program['programName'])) {
            return null;
        }
        
        // Create or find the program
        $programModel = \App\Models\Program::firstOrCreate(
            ['name' => $program['programName']],
            [
                'abbreviation' => $program['nameAbbreviation'] ?? null,
                'description' => $program['programDescription'] ?? null,
            ]
        );
        
        return [
            'id' => $programModel->id,
            'dime_id' => $program['id'] ?? null,
            'name' => $program['programName'] ?? null,
            'abbreviation' => $program['nameAbbreviation'] ?? null,
            'description' => $program['programDescription'] ?? null,
        ];
    }

    /**
     * Build metadata array from raw data
     */
    protected function buildMetadata(array $rawData, array $additionalData = []): array
    {
        $metadata = [
            'source' => 'dime',
            'scraped_at' => now()->toIso8601String(),
            'raw_status' => $rawData['status'] ?? null,
        ];
        
        // Merge additional data
        $metadata = array_merge($metadata, $additionalData);
        
        return $metadata;
    }

    /**
     * Extract JSON data from Next.js HTML response
     */
    protected function extractData(string $content): ?array
    {
        // First try as pure JSON
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        // Extract Next.js __NEXT_DATA__ script tag
        if (preg_match('/<script\s+id="__NEXT_DATA__"\s+type="application\/json">(.+?)<\/script>/s', $content, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                Log::info('DIME scraper extracted data from Next.js HTML');
                return $jsonData['props'] ?? $jsonData;
            }
        }
        
        Log::warning('DIME scraper could not extract data from response');
        return null;
    }
}