# USISA-PH Database Schema Documentation

## Overview

This document describes the database schema for the USISA-PH Laravel application, designed to store and manage government project data based on DIME GOV JSON format specifications.

## Database Design Principles

- **Normalization**: Proper relational database design to avoid data duplication
- **Modularity**: Separate tables for different entities (geographic, programs, offices, etc.)
- **Scalability**: Designed to handle large volumes of government project data  
- **Filament Compatibility**: Models optimized for Laravel Filament admin panel
- **Soft Deletes**: All major entities support soft deletion for data integrity
- **Proper Indexing**: Strategic indexes for performance optimization

## Table Structure

### Geographic Reference Tables

#### `regions`
Stores Philippine administrative regions (PSGC compliant).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| code | varchar(20) | PSGC region code |
| name | varchar | Region name |
| abbreviation | varchar(10) | Region abbreviation |
| sort_order | integer | Display order |
| is_active | boolean | Active status |

#### `provinces` 
Provincial administrative divisions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| region_id | bigint | Foreign key to regions |
| code | varchar(20) | PSGC province code |
| name | varchar | Province name |
| abbreviation | varchar(10) | Province abbreviation |

#### `cities`
City and municipality data.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| province_id | bigint | Foreign key to provinces |
| code | varchar(20) | PSGC city/municipality code |
| name | varchar | City/Municipality name |
| type | varchar(20) | 'city' or 'municipality' |
| zip_code | varchar(10) | Postal code |

#### `barangays`
Barangay (smallest administrative unit) data.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| city_id | bigint | Foreign key to cities |
| code | varchar(20) | PSGC barangay code |
| name | varchar | Barangay name |

### Core Entity Tables

#### `programs`
Government programs and initiatives.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Program name |
| name_abbreviation | varchar(20) | Program abbreviation |
| description | text | Program description |
| slug | varchar | URL-friendly identifier |

#### `implementing_offices`
Government agencies and offices.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Office name (e.g., DPWH) |
| name_abbreviation | varchar(20) | Office abbreviation |
| logo_url | varchar | Office logo URL |
| description | text | Office description |
| website | varchar | Official website |
| email | varchar | Contact email |
| phone | varchar(20) | Contact phone |
| address | text | Office address |

#### `source_of_funds`
Funding sources for projects.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Fund source name |
| name_abbreviation | varchar(20) | Source abbreviation |
| type | varchar(50) | Fund type (GAA, Loan, Grant) |
| fiscal_year | varchar(10) | Associated fiscal year |

#### `contractors`
Contractor companies and entities.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Contractor name |
| name_abbreviation | varchar(20) | Contractor abbreviation |
| business_type | varchar(50) | Type of business |
| license_no | varchar(50) | License number |
| tin | varchar(20) | Tax Identification Number |

### Main Project Table

#### `projects`
Central table storing all project information.

**Key Fields:**
- `project_name`: Official project name
- `project_code`: Unique project identifier
- `description`: Project description
- `slug`: URL-friendly identifier
- `status`: Current project status
- `publication_status`: Draft/Published status
- `cost`: Total project cost (decimal 15,2)
- `utilized_amount`: Amount utilized so far
- Geographic relationships to region/province/city/barangay
- Timeline fields (dates for start, completion, etc.)

### Many-to-Many Relationship Tables

#### `project_implementing_offices`
Links projects to implementing offices.

| Column | Type | Description |
|--------|------|-------------|
| project_id | bigint | Foreign key to projects |
| implementing_office_id | bigint | Foreign key to implementing_offices |
| role | varchar(50) | Role in project |
| is_primary | boolean | Primary implementing office flag |

#### `project_source_of_funds`
Links projects to funding sources.

| Column | Type | Description |
|--------|------|-------------|
| project_id | bigint | Foreign key to projects |
| source_of_fund_id | bigint | Foreign key to source_of_funds |
| allocated_amount | decimal(15,2) | Amount allocated |
| utilized_amount | decimal(15,2) | Amount utilized |
| is_primary | boolean | Primary funding source flag |

#### `project_contractors`
Links projects to contractors.

| Column | Type | Description |
|--------|------|-------------|
| project_id | bigint | Foreign key to projects |
| contractor_id | bigint | Foreign key to contractors |
| contractor_type | varchar(50) | Type of contractor role |
| contract_amount | decimal(15,2) | Contract amount |
| contract_start_date | date | Contract start date |
| contract_end_date | date | Contract end date |
| contract_number | varchar(100) | Contract reference |

### Progress and Resource Tables

#### `project_progresses`
Project progress updates and milestones.

| Column | Type | Description |
|--------|------|-------------|
| project_id | bigint | Foreign key to projects |
| title | varchar | Progress update title |
| description | text | Progress description |
| physical_progress | decimal(5,2) | Physical progress % |
| financial_progress | decimal(5,2) | Financial progress % |
| progress_date | date | Date of update |
| attachments | json | Photos/documents |

#### `project_resources`
Project-related files and documents.

| Column | Type | Description |
|--------|------|-------------|
| project_id | bigint | Foreign key to projects |
| title | varchar | Resource title |
| type | varchar(50) | Resource type |
| file_url | varchar | File URL |
| file_name | varchar | Original filename |
| file_mime_type | varchar(100) | MIME type |
| is_public | boolean | Public access flag |

## Relationships Overview

```
Region (1) -> (N) Province (1) -> (N) City (1) -> (N) Barangay
                                        |
Program (1) -> (N) Project (N) <- (N) City
                    |
                    |-> (N) ProjectProgress
                    |-> (N) ProjectResource
                    |-> (N) ImplementingOffice (via pivot)
                    |-> (N) SourceOfFund (via pivot)
                    |-> (N) Contractor (via pivot)
```

## Sample Data

The seeders include sample data based on the DIME GOV JSON format:

- **Region**: Northern Mindanao (100000000)
- **Province**: Misamis Oriental (104300000)  
- **City**: Cagayan de Oro City (104305000)
- **Barangay**: Lumbia (104305054)
- **Program**: Flood Control Infrastructure
- **Office**: Department of Public Works and Highways (DPWH)

## Performance Considerations

### Indexes
- Geographic hierarchy indexes for efficient location queries
- Status indexes for filtering projects
- Cost indexes for budget-based queries
- Composite indexes for common query patterns

### Optimization Tips
- Use eager loading for relationships in Eloquent
- Implement database-level constraints for data integrity
- Consider pagination for large datasets
- Use appropriate data types (decimal for currency, dates for timestamps)

## Migration Commands

```bash
# Run all migrations
php artisan migrate

# Seed sample data
php artisan db:seed

# Rollback migrations (if needed)
php artisan migrate:rollback

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

## Model Usage Examples

```php
// Get all projects in Northern Mindanao with their implementing offices
$projects = Project::with(['implementingOffices', 'region'])
    ->whereHas('region', function($q) {
        $q->where('name', 'Northern Mindanao');
    })
    ->get();

// Get DPWH flood control projects
$floodProjects = Project::with(['program', 'implementingOffices'])
    ->whereHas('program', function($q) {
        $q->where('name_abbreviation', 'FCI');
    })
    ->whereHas('implementingOffices', function($q) {
        $q->where('name_abbreviation', 'DPWH');
    })
    ->get();

// Calculate total budget by region
$budgetByRegion = Project::join('regions', 'projects.region_id', '=', 'regions.id')
    ->selectRaw('regions.name as region_name, SUM(projects.cost) as total_budget')
    ->groupBy('regions.id', 'regions.name')
    ->get();
```

## Future Extensibility

The schema is designed to be extensible for:
- Additional project types and categories
- Custom fields via JSON metadata columns
- Audit trails and change tracking
- Integration with external systems
- Multi-language support
- Advanced reporting and analytics
