# Claude Assistant Context for USISA-PH Laravel Project

## Quick Reference - MUST READ

### ⚠️ CRITICAL: You are working with Filament v4.0.7
**ALL action imports MUST use `Filament\Actions\*` namespace:**
```php
use Filament\Actions\Action;             // ✅ CORRECT for v4 (base Action class)
use Filament\Actions\EditAction;         // ✅ CORRECT for v4
use Filament\Actions\ViewAction;         // ✅ CORRECT for v4
use Filament\Actions\DeleteAction;       // ✅ CORRECT for v4
use Filament\Actions\BulkActionGroup;    // ✅ CORRECT for v4
use Filament\Actions\DeleteBulkAction;   // ✅ CORRECT for v4

// NEVER USE THESE - They don't exist in v4:
// ❌ use Filament\Tables\Actions\Action;
// ❌ use Filament\Tables\Actions\EditAction;
// ❌ use Filament\Tables\Actions\ViewAction;
```

### Framework Versions
- Laravel 12.28.1
- Filament 4.0.7
- PHP ^8.2
- PostgreSQL database
- Tailwind CSS 4.0.0
- Vite 7.0.4

## Project Purpose
Philippine infrastructure project management system with comprehensive geographic data integration using PSA PSGC standards.

## Database Schema Highlights

### Geographic Hierarchy
```
regions (17) → provinces (81) → cities (1,634) → barangays (42,046)
```

### Key Model Traits
- All models use `HasUuid` trait (UUID v7)
- Geographic models have `psa_code`, `psa_slug`, `island_group_code`
- Soft deletes enabled on most models

## Common Command Patterns

### When Creating Filament Resources
```php
// RelationManager MUST use Schema not Form
public function form(Schema $schema): Schema  // ✅ CORRECT
{
    return $schema->components([...]);  // components not schema
}
```

### Table Actions Pattern
```php
->actions([
    Action::make('custom'),  // Filament\Actions\Action (ALL actions in v4)
    EditAction::make(),      // Filament\Actions\EditAction
    DeleteAction::make(),    // Filament\Actions\DeleteAction
])
```

## Git Commit Rules
1. NO AI/Claude/Co-Authored mentions
2. Use conventional commits
3. Be specific and concise

## Common Issues & Solutions

### Issue: "Class not found" errors in Filament
**Solution**: Check you're using `Filament\Actions\*` not `Filament\Tables\Actions\*`

### Issue: PostgreSQL migration rollback errors
**Solution**: Use conditional column checks:
```php
if (!Schema::hasColumn('table', 'column')) {
    $table->string('column');
}
```

### Issue: Duplicate slugs in geographic data
**Solution**: Append PSGC code to slug:
```php
'psa_slug' => Str::slug($name . '-' . $code)
```

## Quick Commands
```bash
# Import all geographic data
php artisan psgc:import --clear

# Clear all caches
php artisan cache:clear && php artisan view:clear && php artisan filament:cache-components

# Run development environment
composer dev

# Fix code style
./vendor/bin/pint
```

## Data Import Sources
- PSGC API: `https://psgc.gitlab.io/api/`
- Endpoints: regions.json, provinces.json, cities.json, municipalities.json, barangays.json

## Project Structure
```
app/
├── Filament/Resources/     # Admin panel resources
│   ├── Regions/            # Each with Pages, Tables, Schemas
│   ├── Provinces/          # and RelationManagers
│   ├── Cities/
│   └── Barangays/
├── Models/                 # Eloquent models
├── Services/Scrapers/      # Web scraping services
└── Console/Commands/       # Artisan commands
```

## Testing Approach
- Use Pest PHP for testing
- Run with `./vendor/bin/pest`
- Focus on feature and unit tests

## Remember
- This is Filament v4, not v3
- Always verify imports before suggesting code
- PostgreSQL requires special handling for constraints
- Geographic data uses hierarchical relationships
- UUID v7 for time-ordered identifiers