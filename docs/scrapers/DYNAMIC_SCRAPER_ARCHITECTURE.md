# Dynamic Scraper Architecture

## Overview
The dynamic scraper architecture provides a flexible, source-agnostic approach to handling data from multiple government transparency portals. Instead of hard-coding source-specific IDs (like `dime_id`, `sumbong_id`), the system uses generic fields that can accommodate any data source.

## Key Design Principles

### 1. Source Agnostic
- No hard-coded source-specific fields in the database
- Generic `external_id` and `external_source` fields for all sources
- Clean separation of concerns between data sources

### 2. Scalability
- Easy to add new data sources without database migrations
- No need to modify existing code when adding sources
- Consistent interface for all scrapers

### 3. Data Integrity
- Composite unique constraint on `external_id` + `external_source`
- Prevents duplicate entries from the same source
- Allows same ID from different sources

## Database Schema

### Projects Table
```sql
-- Generic external tracking fields
external_id         VARCHAR(255)  -- ID from external source
external_source     VARCHAR(255)  -- Source identifier (e.g., 'dime', 'sumbongsapangulo')
data_source        VARCHAR(255)  -- Legacy field for backward compatibility

-- Unique constraint
UNIQUE KEY unique_external_source (external_id, external_source)

-- Indexes for performance
INDEX idx_external_source (external_source)
```

## Implementation

### Base Scraper Strategy
All scrapers must return data with these required fields:
- `external_id` - The unique identifier from the source system
- `external_source` - The source system code (must match ScraperSource.code)

### Adding a New Data Source

1. **Create Scraper Strategy**
```php
class NewSourceScraperStrategy extends BaseScraperStrategy
{
    public function processData(array $rawData): array
    {
        return [
            'external_id' => $rawData['id'],
            'external_source' => 'newsource',
            // ... other fields
        ];
    }
    
    public function getUniqueField(): string
    {
        return 'external_id';
    }
}
```

2. **Register in Seeder**
```php
ScraperSource::updateOrCreate(
    ['code' => 'newsource'],
    [
        'name' => 'New Source Name',
        'scraper_class' => 'App\\Services\\Scrapers\\NewSourceScraperStrategy',
        // ... configuration
    ]
);
```

3. **No Database Migration Required!**
The existing schema supports any new source automatically.

## Field Mapping Strategy

### Handling Different Field Naming Conventions

Different APIs use different naming conventions:
- camelCase: `projectName`, `dateStarted`
- PascalCase: `ProjectName`, `DateStarted`
- snake_case: `project_name`, `date_started`

The scraper strategies handle these variations:

```php
// Support both PascalCase and camelCase
$projectName = $data['ProjectName'] ?? $data['projectName'] ?? null;

// Map to consistent internal format
return [
    'project_name' => $this->cleanText($projectName),
    // ...
];
```

## Querying Data

### By Source
```php
// Get all projects from DIME
$dimeProjects = Project::where('external_source', 'dime')->get();

// Get all projects from Sumbong Sa Pangulo
$sumbongProjects = Project::where('external_source', 'sumbongsapangulo')->get();
```

### By External ID
```php
// Find a specific project from a source
$project = Project::where('external_source', 'dime')
    ->where('external_id', '12345')
    ->first();
```

### Cross-Source Analysis
```php
// Count projects by source
$projectsBySource = Project::groupBy('external_source')
    ->selectRaw('external_source, count(*) as total')
    ->get();
```

## Benefits

1. **No Code Smell**: Eliminates hard-coded source-specific fields
2. **DRY Principle**: Single migration handles all sources
3. **Open/Closed Principle**: Open for extension (new sources), closed for modification
4. **Maintainability**: Easier to maintain and debug
5. **Performance**: Proper indexing ensures fast queries

## Migration Path

### From Hard-Coded to Dynamic
If migrating from hard-coded fields (like `dime_id`):

1. Run migration to add dynamic fields
2. Migrate existing data:
```sql
UPDATE projects 
SET external_id = dime_id, 
    external_source = 'dime' 
WHERE dime_id IS NOT NULL;
```
3. Drop old columns after verification

## Testing

### Unit Tests
```php
public function test_scraper_uses_dynamic_fields()
{
    $scraper = new DimeScraperStrategy($source);
    $data = $scraper->processData($rawData);
    
    $this->assertArrayHasKey('external_id', $data);
    $this->assertArrayHasKey('external_source', $data);
    $this->assertEquals('dime', $data['external_source']);
}
```

### Integration Tests
```php
public function test_prevents_duplicate_from_same_source()
{
    Project::create([
        'external_id' => '123',
        'external_source' => 'dime',
        // ...
    ]);
    
    $this->expectException(QueryException::class);
    Project::create([
        'external_id' => '123',
        'external_source' => 'dime',
        // ...
    ]);
}

public function test_allows_same_id_from_different_sources()
{
    Project::create([
        'external_id' => '123',
        'external_source' => 'dime',
        // ...
    ]);
    
    $project = Project::create([
        'external_id' => '123',
        'external_source' => 'sumbongsapangulo',
        // ...
    ]);
    
    $this->assertNotNull($project->id);
}
```

## Future Enhancements

1. **Source Priority**: Add priority field for conflict resolution
2. **Data Merging**: Combine data from multiple sources for the same project
3. **Source Versioning**: Track API version changes
4. **Field Mapping Configuration**: Move field mappings to database configuration
5. **Automatic Source Detection**: Detect source from API response structure