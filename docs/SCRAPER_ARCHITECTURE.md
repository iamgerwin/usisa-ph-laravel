# Data Scraper Architecture and Resilience

## Overview
This document describes the robust data scraping system implemented for the USISA PH Laravel project. The system is designed to handle large-scale data extraction with built-in resilience, duplicate prevention, and automatic recovery mechanisms.

## Architecture Components

### 1. Strategy Pattern Implementation

#### Base Strategy
Location: `/app/Services/Scrapers/BaseScraperStrategy.php`

The base strategy provides:
- HTTP client configuration with timeouts
- Exponential backoff retry logic
- Error handling and logging
- Abstract methods for implementation

#### DIME Scraper Strategy
Location: `/app/Services/Scrapers/DimeScraperStrategy.php`

Specific implementation for DIME data source:
- Custom data parsing logic
- Field mapping and transformation
- Relationship handling

### 2. Command Structure

#### Main Scrape Command
Location: `/app/Console/Commands/ScrapeDataCommand.php`

Features:
- **Job Management**: Creates and tracks scraper jobs
- **Conflict Detection**: Prevents overlapping jobs
- **Progress Tracking**: Real-time progress updates
- **Auto-Recovery**: Resumes from last successful point

Command Options:
```bash
php artisan scrape:data --source=dime --start=1 --end=100000 --chunk=100 --delay=1
php artisan scrape:data --source=dime --resume
php artisan scrape:data --source=dime --start=1 --end=100 --dry-run
```

### 3. Resilience Features

#### Connection Management
```php
'connect_timeout' => 10,  // 10 seconds to establish connection
'timeout' => 30,          // 30 seconds total timeout
'http_errors' => false,   // Handle errors gracefully
```

#### Exponential Backoff
- Initial delay: 2 seconds
- Maximum delay: 32 seconds
- Maximum retries: 5
- Backoff multiplier: 2x

#### Auto-Save Mechanism
- Progress saved every 10 batches
- Prevents data loss on interruption
- Enables seamless resume capability

#### Auto-Pause Feature
- Automatically pauses after 2 hours
- Prevents resource exhaustion
- Allows for scheduled maintenance windows

### 4. Duplicate Prevention

#### Multi-Layer Strategy

1. **Database Constraints**
   - Unique index on `dime_id`
   - Unique index on `project_code`
   - Composite indexes for performance

2. **Application Logic**
   ```php
   // Skip if recently synced (within 1 hour)
   if ($existingProject && $existingProject->last_synced_at && 
       $existingProject->last_synced_at->gt(now()->subHour())) {
       return;
   }
   ```

3. **Transaction Locking**
   ```php
   DB::transaction(function () use ($data) {
       $project = Project::where('dime_id', $data['dime_id'])
           ->lockForUpdate()
           ->first();
       // Update or create logic
   });
   ```

### 5. Job Status Management

#### Status Types
- **PENDING**: Job created but not started
- **RUNNING**: Currently processing
- **COMPLETED**: Successfully finished
- **FAILED**: Encountered unrecoverable error
- **PAUSED**: Manually or automatically paused
- **CANCELLED**: User-initiated cancellation

#### Job Overlap Prevention
```php
protected function checkForConflictingJobs(int $startId, int $endId): void
{
    $conflictingJobs = ScraperJob::where('source_id', $this->source->id)
        ->whereIn('status', [ScraperJobStatus::RUNNING, ScraperJobStatus::PENDING])
        ->where(function ($query) use ($startId, $endId) {
            $query->whereBetween('start_id', [$startId, $endId])
                ->orWhereBetween('end_id', [$startId, $endId])
                ->orWhere(function ($q) use ($startId, $endId) {
                    $q->where('start_id', '<=', $startId)
                      ->where('end_id', '>=', $endId);
                });
        })->get();
}
```

## Data Flow

### 1. Extraction Process
```
Command Initiated → Job Created → Validate No Conflicts → 
Begin Processing → Fetch Data → Transform → 
Check Duplicates → Save/Update → Update Progress → 
Auto-Save Checkpoint → Continue/Pause/Complete
```

### 2. Error Recovery Flow
```
Error Detected → Log Error → 
Exponential Backoff → Retry → 
Success: Continue | Failure: Mark Failed & Exit
```

### 3. Resume Process
```
Resume Command → Find Last Job → 
Load Last Position → Validate State → 
Continue from Checkpoint → Normal Processing
```

## Database Schema

### scraper_sources Table
```sql
- uuid (primary public identifier)
- id (internal identifier)
- name (source name)
- type (scraper type)
- base_url (API endpoint)
- is_active (enable/disable)
- configuration (JSON settings)
```

### scraper_jobs Table
```sql
- uuid (primary public identifier)
- id (internal identifier)
- source_id (foreign key)
- status (job status)
- start_id (range start)
- end_id (range end)
- current_position (progress tracker)
- started_at (timestamp)
- completed_at (timestamp)
- error_log (JSON error details)
```

## Monitoring and Maintenance

### Health Checks

1. **Active Jobs Monitor**
   ```bash
   php artisan scrape:status
   ```

2. **Data Integrity Verification**
   ```bash
   php artisan verify:data-integrity
   ```

3. **Duplicate Detection**
   ```sql
   SELECT dime_id, COUNT(*) as count
   FROM projects
   WHERE dime_id IS NOT NULL
   GROUP BY dime_id
   HAVING COUNT(*) > 1;
   ```

### Performance Metrics

Monitor these key indicators:
- Average response time per request
- Success rate percentage
- Duplicate prevention effectiveness
- Memory usage during processing
- Database query performance

### Troubleshooting Guide

#### Stuck Jobs
1. Identify stuck job: `ps aux | grep scrape`
2. Kill process: `kill -9 [PID]`
3. Mark job as failed in database
4. Resume with `--resume` flag

#### Memory Issues
1. Reduce chunk size
2. Increase delay between requests
3. Enable query logging to identify leaks
4. Use `--dry-run` to test without saving

#### Duplicate Data
1. Run integrity check
2. Identify source of duplicates
3. Clean duplicates with backup
4. Re-enable constraints

## Best Practices

### 1. Chunk Size Selection
- Small chunks (25-50): For unstable connections
- Medium chunks (100-200): Standard operation
- Large chunks (500+): Stable, high-speed connections

### 2. Delay Configuration
- Minimum: 1 second (respect rate limits)
- Standard: 2-3 seconds (balanced approach)
- Conservative: 5+ seconds (avoid blocking)

### 3. Scheduling Recommendations
- Run during off-peak hours
- Implement rotating schedules
- Use Laravel's task scheduling
- Monitor resource usage

### 4. Data Validation
- Implement field-level validation
- Check data completeness
- Verify relationships
- Log anomalies for review

## Error Handling

### Common Errors and Solutions

1. **Connection Timeout**
   - Increase timeout values
   - Check network stability
   - Verify endpoint availability

2. **Rate Limiting (429)**
   - Increase delay between requests
   - Implement adaptive rate limiting
   - Contact API provider if persistent

3. **Data Format Changes**
   - Update parser logic
   - Add flexible field mapping
   - Implement schema versioning

4. **Database Conflicts**
   - Review unique constraints
   - Check transaction isolation
   - Implement retry logic

## Future Enhancements

1. **Parallel Processing**
   - Queue-based architecture
   - Multiple worker processes
   - Load balancing

2. **Machine Learning Integration**
   - Anomaly detection
   - Predictive maintenance
   - Smart scheduling

3. **Advanced Monitoring**
   - Real-time dashboards
   - Alert systems
   - Performance analytics

4. **Data Quality Metrics**
   - Completeness scoring
   - Accuracy validation
   - Consistency checks

## Conclusion

The scraper architecture provides a robust, scalable solution for data extraction with comprehensive error handling, duplicate prevention, and recovery mechanisms. The system is designed to handle large-scale operations while maintaining data integrity and system stability.