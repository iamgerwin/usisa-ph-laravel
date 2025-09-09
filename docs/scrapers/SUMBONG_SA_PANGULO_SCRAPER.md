# Sumbong Sa Pangulo Scraper Documentation

## Overview
The Sumbong Sa Pangulo scraper is designed to extract project data from the SumbongSaPangulo.ph government transparency portal. This scraper handles both PascalCase and camelCase field variations in the API responses.

## Configuration

### Scraper Source
- **Code**: `sumbongsapangulo`
- **Name**: SumbongSaPangulo.ph
- **Base URL**: `https://www.sumbongsapangulo.ph/api`
- **Endpoint Pattern**: `/projects/{id}`
- **Scraper Class**: `App\Services\Scrapers\SumbongSaPanguloScraperStrategy`

### Rate Limiting
- **Rate Limit**: 10 requests per second
- **Timeout**: 30 seconds
- **Retry Attempts**: 3
- **Rate Limit Delay**: 60 seconds

## Features

### Case-Insensitive Field Handling
The scraper automatically handles both PascalCase and camelCase field names:
- `ProjectName` or `projectName`
- `DateStarted` or `dateStarted`
- `Cost` or `cost`

### Data Processing

#### Primary Fields Mapped
- `external_id` - Unique identifier from external source
- `external_source` - Source system identifier (set to 'sumbongsapangulo')
- `project_name` - Name of the project
- `project_code` - Project reference code
- `description` - Project description
- `status` - Current project status (mapped to internal enum)
- `cost` - Total project cost
- `utilized_amount` - Amount already utilized

#### Location Fields
- `street_address`, `city_name`, `city_code`
- `barangay_name`, `barangay_code`
- `province_name`, `province_code`
- `region_name`, `region_code`
- `latitude`, `longitude`

#### Date Fields
- `date_started` - Project start date
- `actual_date_started` - Actual start date
- `contract_completion_date` - Expected completion
- `actual_contract_completion_date` - Actual completion

#### Related Entities
- **Implementing Offices** - Government offices managing the project
- **Contractors** - Companies executing the project
- **Source of Funds** - Funding sources
- **Programs** - Government programs associated with the project

### Status Mapping
The scraper maps various status strings to internal ProjectStatus enum:
- `ongoing`, `in_progress`, `active` → ACTIVE
- `completed`, `finished` → COMPLETED
- `pending`, `planned` → PENDING
- `suspended`, `on_hold` → ON_HOLD
- `cancelled`, `terminated` → CANCELLED

## Usage

### Running the Scraper
```bash
# Scrape specific project ID range
php artisan scrape:data --source=sumbongsapangulo --start=1 --end=100

# Resume from last position
php artisan scrape:data --source=sumbongsapangulo --resume

# Dry run mode
php artisan scrape:data --source=sumbongsapangulo --dry-run
```

### Testing the Scraper
```bash
# Test with default sample data
php artisan test:sumbong-scraper

# Test with custom JSON data
php artisan test:sumbong-scraper --data='{"Id":123,"ProjectName":"Test Project"}'
```

### Seeding the Source
```bash
php artisan db:seed --class=ScraperSourceSeeder
```

### Running Migrations
```bash
# Add sumbong_id field to projects table
php artisan migrate
```

## Data Validation
The scraper validates incoming data by checking for:
1. Project ID (`Id` or `id`)
2. Project Name (`ProjectName` or `projectName`)
3. Next.js response structure (`pageProps.project`)

## Error Handling
- Automatic retry on failure (up to 3 attempts)
- Exponential backoff between retries
- Comprehensive error logging
- Graceful handling of missing fields

## Metadata Storage
Additional data not mapped to database columns is stored in the `metadata` JSON field:
- Physical progress percentage
- Contractor name (if not normalized)
- Project location description
- Resources and progress updates
- Original creation and update timestamps

## Integration Points

### Models
- **Project**: Main entity storing project data
- **Program**: Government programs (created on-demand)
- **ImplementingOffice**: To be normalized (currently in metadata)
- **Contractor**: To be normalized (currently in metadata)
- **SourceOfFund**: To be normalized (currently in metadata)

### Database Fields
The scraper requires these database fields:
- `external_id` (string, nullable, indexed with external_source)
- `external_source` (string, nullable, indexed)
- All standard project fields from the Project model
- `metadata` (JSON) for additional data storage

## Performance Considerations
- Batch processing with configurable chunk size
- Transaction-based database operations
- Memory-efficient processing
- Progress tracking and resumable operations

## Monitoring
Track scraper performance through:
- ScraperJob status and progress
- Success/error/skip counters
- Processing duration metrics
- Laravel logs for detailed debugging

## Future Enhancements
1. Normalize implementing offices, contractors, and funding sources
2. Implement incremental updates based on last modified dates
3. Add support for bulk API endpoints if available
4. Implement webhook notifications for completed jobs
5. Add data quality validation and reporting