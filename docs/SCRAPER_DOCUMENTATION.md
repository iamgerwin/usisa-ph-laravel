# Data Scraper System Documentation

## Overview

The Data Scraper System is a robust, configurable Laravel-based solution for scraping project data from various government sources. It implements the Strategy Pattern for flexibility and supports multiple data sources with database-driven configuration.

## Architecture

### Design Patterns

- **Strategy Pattern**: Allows different scraping strategies for various data sources
- **Repository Pattern**: Database-driven configuration management
- **Observer Pattern**: Job progress tracking and event handling

### Key Components

1. **ScraperStrategy Interface** (`app/Contracts/ScraperStrategy.php`)
   - Defines the contract for all scraper implementations
   - Methods for scraping, processing, and validating data

2. **BaseScraperStrategy** (`app/Services/Scrapers/BaseScraperStrategy.php`)
   - Abstract base class with common functionality
   - HTTP client management
   - Rate limiting and retry logic
   - Error handling and logging

3. **DimeScraperStrategy** (`app/Services/Scrapers/DimeScraperStrategy.php`)
   - Specific implementation for DIME.gov.ph
   - Field mapping and data transformation
   - Status mapping to internal enums

## Database Schema

### scraper_sources
Stores configuration for each data source:
- `code`: Unique identifier (e.g., 'dime')
- `name`: Display name
- `base_url`: API base URL
- `endpoint_pattern`: URL pattern with {id} placeholder
- `is_active`: Enable/disable flag
- `rate_limit`: Requests per second
- `timeout`: Request timeout in seconds
- `retry_attempts`: Number of retries
- `headers`: JSON field for HTTP headers
- `field_mapping`: JSON mapping of API fields to model fields
- `metadata`: Additional configuration
- `scraper_class`: PHP class for the strategy
- `version`: Configuration version

### scraper_jobs
Tracks scraping job progress:
- `source_id`: Foreign key to scraper_sources
- `start_id`, `end_id`: ID range to scrape
- `current_id`: Current progress
- `chunk_size`: Batch size
- `status`: Job status (enum)
- `success_count`, `error_count`, `skip_count`: Counters
- `update_count`, `create_count`: Operation counters
- `stats`: JSON field for additional metrics
- `errors`: JSON log of errors
- `triggered_by`: User or system that started the job

## Enums

### ScraperJobStatus
```php
- PENDING: Job created but not started
- RUNNING: Currently processing
- COMPLETED: Successfully finished
- FAILED: Stopped due to errors
- PAUSED: Temporarily stopped
- CANCELLED: Manually terminated
```

## Command Usage

### Basic Usage
```bash
php artisan scrape:data --source=dime --start=1 --end=1000
```

### All Options
```bash
php artisan scrape:data
    --source=dime       # Source code
    --start=1           # Starting ID
    --end=1000         # Ending ID
    --chunk=100        # Batch size
    --delay=1          # Seconds between requests
    --retry=3          # Retry attempts
    --resume           # Resume last failed/paused job
    --job=123          # Resume specific job ID
    --dry-run          # Test without saving
    --verbose          # Detailed output
```

### Examples

1. **Start new scraping job**:
```bash
php artisan scrape:data --source=dime --start=1 --end=5000 --chunk=50
```

2. **Resume failed job**:
```bash
php artisan scrape:data --source=dime --resume
```

3. **Dry run for testing**:
```bash
php artisan scrape:data --source=dime --start=1 --end=10 --dry-run --verbose
```

4. **Resume specific job**:
```bash
php artisan scrape:data --job=42
```

## Admin Panel

### Scraper Sources Management
- Navigate to `/admin/scraper-sources`
- Add/edit scraper configurations
- Configure field mappings
- Set rate limits and timeouts
- Add custom HTTP headers

### Scraper Jobs Monitoring
- Navigate to `/admin/scraper-jobs`
- View job progress and statistics
- Monitor error logs
- Resume/cancel jobs

### Running Scrapers from UI
1. Go to Scraper Sources
2. Click "Run Scraper" action on any active source
3. Configure parameters in the modal
4. Monitor progress in Scraper Jobs

## Adding New Data Sources

### Step 1: Create Strategy Class
```php
namespace App\Services\Scrapers;

class NewSourceStrategy extends BaseScraperStrategy
{
    public function processData(array $rawData): array
    {
        // Transform raw data to model format
    }
    
    public function validateData(array $data): bool
    {
        // Validate required fields
    }
    
    public function getModelClass(): string
    {
        return Project::class;
    }
    
    public function getUniqueField(): string
    {
        return 'source_id';
    }
}
```

### Step 2: Add Database Configuration
```php
ScraperSource::create([
    'code' => 'newsource',
    'name' => 'New Source',
    'base_url' => 'https://api.newsource.gov.ph',
    'endpoint_pattern' => '/api/projects/{id}',
    'scraper_class' => 'App\\Services\\Scrapers\\NewSourceStrategy',
    // ... other configuration
]);
```

### Step 3: Configure Field Mapping
In the admin panel or database:
```json
{
    "title": "project_title",
    "description": "project_desc",
    "cost": "total_budget",
    "status": "current_status"
}
```

## Error Handling

### Automatic Retry
- Failed requests are retried based on `retry_attempts` configuration
- Exponential backoff between retries
- Rate limit (429) responses trigger automatic delay

### Error Logging
- Errors are logged to the job's `errors` JSON field
- Laravel log files for debugging
- Maximum 1000 errors kept per job to prevent bloat

### Recovery Options
1. **Resume from failure**: `php artisan scrape:data --resume`
2. **Skip problematic IDs**: Continue processing next IDs
3. **Manual intervention**: Fix data/configuration and retry

## Performance Optimization

### Rate Limiting
- Configurable requests per second
- Automatic throttling based on `rate_limit` setting
- Respects API rate limit headers

### Batch Processing
- Process multiple IDs in chunks
- Configurable chunk size for memory management
- Transaction-based database operations

### Progress Tracking
- Real-time progress bar in console
- Database progress updates
- Graceful shutdown with stop file

## Monitoring

### Metrics Available
- Total processed records
- Success/error/skip counts
- Created vs updated records
- Processing duration
- Progress percentage

### Health Checks
```php
// Check if source has running job
$source->hasRunningJob();

// Get source statistics
$stats = $source->getStatistics();

// Check job status
$job->isRunning();
$job->progress_percentage;
$job->formatted_duration;
```

## Testing

### Run Tests
```bash
php artisan test --filter=ScraperCommandTest
```

### Test Coverage
- Command functionality
- Job status transitions
- URL building
- Progress calculations
- Counter methods

## Troubleshooting

### Common Issues

1. **Rate Limiting**
   - Increase delay between requests
   - Reduce rate_limit setting
   - Check API documentation for limits

2. **Memory Issues**
   - Reduce chunk_size
   - Process smaller ID ranges
   - Monitor memory usage

3. **Connection Timeouts**
   - Increase timeout setting
   - Check network connectivity
   - Verify API endpoint availability

### Debug Mode
```bash
php artisan scrape:data --source=dime --verbose --start=1 --end=10
```

### Stop Scraper
Create stop file to gracefully halt:
```bash
touch storage/scraper.stop
```

## Security Considerations

- API keys stored in headers configuration
- Rate limiting to prevent API abuse
- Soft deletes for audit trail
- User authentication for admin panel
- Input validation and sanitization

## Future Enhancements

- Queue-based processing for large datasets
- Webhook notifications for job completion
- API endpoint for programmatic access
- Scheduled scraping via Laravel scheduler
- Data validation and quality checks
- Export functionality for scraped data