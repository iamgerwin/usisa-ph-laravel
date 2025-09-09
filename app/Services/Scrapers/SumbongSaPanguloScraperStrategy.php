<?php

namespace App\Services\Scrapers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SumbongSaPanguloScraperStrategy extends BaseScraperStrategy
{
    /**
     * Process SumbongSaPangulo.ph API data into project model format
     */
    public function processData(array $rawData): array
    {
        // Handle nested structure from Next.js response or direct API response
        $project = $rawData['pageProps']['project'] ?? $rawData;
        
        // Process implementing offices, contractors, and source of funds
        $implementingOffices = $this->processImplementingOffices($project['ImplementingOffices'] ?? $project['implementingOffices'] ?? []);
        $contractors = $this->processContractors($project['Contractors'] ?? $project['contractors'] ?? []);
        $sourceOfFunds = $this->processSourceOfFunds($project['SourceOfFunds'] ?? $project['sourceOfFunds'] ?? []);
        $program = $this->processProgram($project['Program'] ?? $project['program'] ?? null);
        
        return [
            'external_id' => $project['Id'] ?? $project['id'] ?? null,
            'external_source' => 'sumbongsapangulo',
            'project_name' => $this->cleanText($project['ProjectName'] ?? $project['projectName'] ?? ''),
            'project_code' => $project['ProjectCode'] ?? $project['projectCode'] ?? null,
            'description' => $this->cleanText($project['Description'] ?? $project['description'] ?? ''),
            'project_image_url' => $project['ProjectImageUrl'] ?? $project['projectImageUrl'] ?? null,
            'street_address' => $project['StreetAddress'] ?? $project['streetAddress'] ?? null,
            'city_name' => $project['City'] ?? $project['city'] ?? null,
            'city_code' => $project['CityCode'] ?? $project['cityCode'] ?? null,
            'zip_code' => $project['ZipCode'] ?? $project['zipCode'] ?? null,
            'barangay_name' => $project['Barangay'] ?? $project['barangay'] ?? null,
            'barangay_code' => $project['BarangayCode'] ?? $project['barangayCode'] ?? null,
            'province_name' => $project['Province'] ?? $project['province'] ?? null,
            'province_code' => $project['ProvinceCode'] ?? $project['provinceCode'] ?? null,
            'region_name' => $project['Region'] ?? $project['region'] ?? null,
            'region_code' => $project['RegionCode'] ?? $project['regionCode'] ?? null,
            'country' => $project['Country'] ?? $project['country'] ?? 'Philippines',
            'state' => $project['State'] ?? $project['state'] ?? null,
            'latitude' => $this->parseCoordinate($project['Latitude'] ?? $project['latitude'] ?? null),
            'longitude' => $this->parseCoordinate($project['Longitude'] ?? $project['longitude'] ?? null),
            'status' => $this->mapStatus($project['Status'] ?? $project['status'] ?? null),
            'publication_status' => $project['PublicationStatus'] ?? $project['publicationStatus'] ?? 'Published',
            'cost' => $this->parseAmount($project['Cost'] ?? $project['cost'] ?? $project['ApprovedBudget'] ?? $project['approvedBudget'] ?? 0),
            'utilized_amount' => $this->parseAmount($project['UtilizedAmount'] ?? $project['utilizedAmount'] ?? null) ?? 0,
            'date_started' => $this->parseDate($project['DateStarted'] ?? $project['dateStarted'] ?? null),
            'actual_date_started' => $this->parseDate($project['ActualDateStarted'] ?? $project['actualDateStarted'] ?? null),
            'contract_completion_date' => $this->parseDate($project['ContractCompletionDate'] ?? $project['contractCompletionDate'] ?? null),
            'actual_contract_completion_date' => $this->parseDate($project['ActualContractCompletionDate'] ?? $project['actualContractCompletionDate'] ?? null),
            'as_of_date' => $this->parseDate($project['AsOfDate'] ?? $project['asOfDate'] ?? null),
            'last_updated_project_cost' => $this->parseDate($project['LastUpdatedProjectCost'] ?? $project['lastUpdatedProjectCost'] ?? null),
            'updates_count' => $project['UpdatesCount'] ?? $project['updatesCount'] ?? 0,
            'program_id' => $program['id'] ?? null,
            'data_source' => 'sumbongsapangulo',
            'last_synced_at' => now(),
            'metadata' => $this->buildMetadata($project, [
                'implementing_offices' => $implementingOffices,
                'contractors' => $contractors,
                'source_of_funds' => $sourceOfFunds,
                'program' => $program,
                'resources' => $project['Resources'] ?? $project['resources'] ?? [],
                'progresses' => $project['Progresses'] ?? $project['progresses'] ?? [],
                'physical_progress' => $project['PhysicalProgress'] ?? $project['physicalProgress'] ?? null,
                'contractor_name' => $project['ContractorName'] ?? $project['contractorName'] ?? null,
                'project_location' => $project['ProjectLocation'] ?? $project['projectLocation'] ?? null,
                'source_created_at' => $project['CreatedAt'] ?? $project['createdAt'] ?? null,
                'source_updated_at' => $project['UpdatedAt'] ?? $project['updatedAt'] ?? null,
            ]),
        ];
    }

    /**
     * Validate Sumbong Sa Pangulo API response data
     */
    public function validateData(array $data): bool
    {
        // Check for Next.js response structure
        if (isset($data['pageProps']['project'])) {
            $project = $data['pageProps']['project'];
            return isset($project['Id']) || isset($project['id']) || 
                   isset($project['ProjectName']) || isset($project['projectName']);
        }
        
        // Check for direct project data (both PascalCase and camelCase)
        return isset($data['Id']) || isset($data['id']) || 
               isset($data['ProjectName']) || isset($data['projectName']);
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
        return 'external_id';
    }

    /**
     * Map Sumbong Sa Pangulo status to our ProjectStatus enum
     */
    protected function mapStatus(?string $status): ?string
    {
        if (!$status) {
            return ProjectStatus::DRAFT->value;
        }
        
        $status = strtolower($status);
        
        return match($status) {
            'ongoing', 'in_progress', 'active', 'in progress' => ProjectStatus::ACTIVE->value,
            'completed', 'finished', 'complete' => ProjectStatus::COMPLETED->value,
            'pending', 'planned', 'for implementation' => ProjectStatus::PENDING->value,
            'suspended', 'on_hold', 'on hold', 'halted' => ProjectStatus::ON_HOLD->value,
            'cancelled', 'terminated', 'abandoned' => ProjectStatus::CANCELLED->value,
            default => ProjectStatus::DRAFT->value,
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
        
        // Handle Philippine peso format (e.g., "₱1,234,567.89" or "PHP 1,234,567.89")
        $value = str_replace(['₱', 'PHP', ',', ' '], '', $value);
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
            Log::warning('Failed to parse date in SumbongSaPangulo scraper', [
                'value' => $value,
                'error' => $e->getMessage()
            ]);
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
        
        // Validate coordinate ranges
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
            
            // Already a percentage (1-100)
            if ($percent > 1 && $percent <= 100) {
                return $percent;
            }
            
            // Decimal format (0-1)
            if ($percent >= 0 && $percent <= 1) {
                return $percent * 100;
            }
        }
        
        // Handle percentage strings (e.g., "50%")
        if (is_string($value) && str_contains($value, '%')) {
            $value = str_replace('%', '', $value);
            return (float) $value;
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
     * Process implementing offices array
     */
    protected function processImplementingOffices(array $offices): array
    {
        return array_map(function ($office) {
            return [
                'id' => $office['Id'] ?? $office['id'] ?? null,
                'name' => $office['Name'] ?? $office['name'] ?? null,
                'abbreviation' => $office['NameAbbreviation'] ?? $office['nameAbbreviation'] ?? null,
                'logo_url' => $office['LogoUrl'] ?? $office['logoUrl'] ?? null,
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
                'id' => $contractor['Id'] ?? $contractor['id'] ?? null,
                'name' => $contractor['Name'] ?? $contractor['name'] ?? null,
                'abbreviation' => $contractor['NameAbbreviation'] ?? $contractor['nameAbbreviation'] ?? null,
                'logo_url' => $contractor['LogoUrl'] ?? $contractor['logoUrl'] ?? null,
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
                'id' => $source['Id'] ?? $source['id'] ?? null,
                'name' => $source['Name'] ?? $source['name'] ?? null,
                'abbreviation' => $source['NameAbbreviation'] ?? $source['nameAbbreviation'] ?? null,
                'logo_url' => $source['LogoUrl'] ?? $source['logoUrl'] ?? null,
            ];
        }, $sources);
    }

    /**
     * Process program data and create if needed
     */
    protected function processProgram(?array $program): ?array
    {
        if (!$program) {
            return null;
        }
        
        $programName = $program['ProgramName'] ?? $program['programName'] ?? null;
        if (!$programName) {
            return null;
        }
        
        // Create or find the program
        $programModel = \App\Models\Program::firstOrCreate(
            ['name' => $programName],
            [
                'abbreviation' => $program['NameAbbreviation'] ?? $program['nameAbbreviation'] ?? null,
                'description' => $program['ProgramDescription'] ?? $program['programDescription'] ?? null,
            ]
        );
        
        return [
            'id' => $programModel->id,
            'external_id' => $program['Id'] ?? $program['id'] ?? null,
            'name' => $programName,
            'abbreviation' => $program['NameAbbreviation'] ?? $program['nameAbbreviation'] ?? null,
            'description' => $program['ProgramDescription'] ?? $program['programDescription'] ?? null,
        ];
    }

    /**
     * Build metadata array from raw data
     */
    protected function buildMetadata(array $rawData, array $additionalData = []): array
    {
        $metadata = [
            'source' => 'sumbongsapangulo',
            'scraped_at' => now()->toIso8601String(),
            'raw_status' => $rawData['Status'] ?? $rawData['status'] ?? null,
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
                Log::info('SumbongSaPangulo scraper extracted data from Next.js HTML');
                return $jsonData['props'] ?? $jsonData;
            }
        }
        
        Log::warning('SumbongSaPangulo scraper could not extract data from response');
        return null;
    }
}