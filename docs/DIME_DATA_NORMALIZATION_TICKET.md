# DIME Data Normalization and Entity Relationships

## Ticket Information
- **Title**: Normalize DIME Data and Establish Entity Relationships
- **Status**: To Do
- **Priority**: High
- **Estimated Time**: 4 hours
- **Tags**: database, normalization, dime, laravel

## Overview
Normalize the scraped DIME data currently stored in the projects table metadata JSON field into proper relational database entities.

## Current State
- All DIME data is stored in the `projects` table
- Related entities (implementing offices, contractors, source of funds, programs) are stored in the `metadata` JSON column
- Need to extract and normalize these relationships

## Tasks

### 1. Create/Update Entity Tables
- [ ] **implementing_offices** table
  - uuid (primary key)
  - dime_id (from DIME API)
  - name
  - abbreviation
  - logo_url
  - created_at, updated_at

- [ ] **contractors** table  
  - uuid (primary key)
  - dime_id (from DIME API)
  - name
  - abbreviation
  - logo_url
  - created_at, updated_at

- [ ] **source_of_funds** table
  - uuid (primary key)
  - dime_id (from DIME API)
  - name
  - abbreviation
  - logo_url
  - created_at, updated_at

- [ ] **programs** table (already exists, needs update)
  - Ensure dime_id field exists
  - Add logo_url field if missing

### 2. Create Pivot Tables
- [ ] **project_implementing_offices**
  - project_uuid
  - implementing_office_uuid
  - created_at

- [ ] **project_contractors**
  - project_uuid
  - contractor_uuid
  - created_at

- [ ] **project_source_of_funds**
  - project_uuid
  - source_of_fund_uuid
  - created_at

### 3. Data Migration Script
- [ ] Create Laravel command `php artisan dime:normalize-data`
- [ ] Extract implementing_offices from metadata JSON
- [ ] Extract contractors from metadata JSON
- [ ] Extract source_of_funds from metadata JSON
- [ ] Create/update entity records (check for duplicates using dime_id)
- [ ] Establish relationships through pivot tables
- [ ] Update projects table to reference program_uuid directly

### 4. Update Models
- [ ] Update Project model with new relationships:
  - belongsToMany implementing_offices
  - belongsToMany contractors
  - belongsToMany source_of_funds
  - belongsTo program

- [ ] Create new models:
  - ImplementingOffice
  - Contractor
  - SourceOfFund

### 5. Update Scraper
- [ ] Modify DimeScraperStrategy to:
  - Create/update related entities during scraping
  - Establish relationships immediately
  - Remove storing in metadata JSON

### 6. Testing
- [ ] Test migration on existing scraped projects
- [ ] Verify all relationships are properly established
- [ ] Ensure no data loss during normalization
- [ ] Test new scraper implementation

## Acceptance Criteria
- All DIME data properly normalized into relational tables
- No duplicate entities (use dime_id for uniqueness)
- All relationships established using UUIDs
- Metadata JSON no longer contains normalized data
- Scraper creates proper relationships for new data
- All models have proper Eloquent relationships defined

## Technical Notes
- Use database transactions for data integrity
- Implement progress tracking for migration command
- Add rollback capability in case of errors
- Log all operations for audit trail

## Implementation Example

```php
// Migration for implementing_offices
Schema::create('implementing_offices', function (Blueprint $table) {
    $table->uuid('uuid')->primary();
    $table->string('dime_id')->unique()->nullable();
    $table->string('name');
    $table->string('abbreviation')->nullable();
    $table->string('logo_url')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// Pivot table
Schema::create('project_implementing_offices', function (Blueprint $table) {
    $table->uuid('project_uuid');
    $table->uuid('implementing_office_uuid');
    $table->timestamps();
    
    $table->foreign('project_uuid')->references('uuid')->on('projects')->onDelete('cascade');
    $table->foreign('implementing_office_uuid')->references('uuid')->on('implementing_offices')->onDelete('cascade');
    $table->primary(['project_uuid', 'implementing_office_uuid']);
});
```

## ClickUp Link
Add to: https://app.clickup.com/90161067116/v/l/s/90165030254