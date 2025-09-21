# Project Configuration Summary

## Framework Versions
- **PHP**: ^8.2
- **Laravel Framework**: 12.28.1
- **Filament**: 4.0.7

## Key PHP Dependencies
- **filament/filament**: ^4.0 - Admin panel framework
- **laravel/framework**: ^12.0 - Core Laravel framework
- **laravel/tinker**: ^2.10.1 - REPL for Laravel
- **spatie/laravel-activitylog**: ^4.10 - Activity logging
- **spatie/laravel-permission**: ^6.21 - Role and permission management
- **spatie/laravel-query-builder**: ^6.3 - API query builder
- **spatie/laravel-sluggable**: ^3.7 - Automatic slug generation
- **spatie/laravel-tags**: ^4.10 - Tagging functionality
- **symfony/dom-crawler**: ^7.3 - DOM crawling for web scraping

## Development Dependencies
- **fakerphp/faker**: ^1.23 - Fake data generation
- **laravel/pail**: ^1.2.2 - Real-time log viewer
- **laravel/pint**: ^1.24 - Code style fixer
- **laravel/sail**: ^1.41 - Docker development environment
- **pestphp/pest**: ^4.0 - Testing framework
- **pestphp/pest-plugin-laravel**: ^4.0 - Laravel integration for Pest

## Frontend Dependencies
- **Tailwind CSS**: ^4.0.0 - Utility-first CSS framework
- **Vite**: ^7.0.4 - Build tool
- **Laravel Vite Plugin**: ^2.0.0 - Laravel integration for Vite
- **Axios**: ^1.11.0 - HTTP client

## Database
- **PostgreSQL**: Production database

## Important Notes for Filament v4

### Action Imports
All action classes in Filament v4 are in the `Filament\Actions\*` namespace:
- `Filament\Actions\EditAction`
- `Filament\Actions\ViewAction`
- `Filament\Actions\DeleteAction`
- `Filament\Actions\BulkActionGroup`
- `Filament\Actions\DeleteBulkAction`
- `Filament\Actions\ForceDeleteBulkAction`
- `Filament\Actions\RestoreBulkAction`

Table-specific custom actions remain in:
- `Filament\Tables\Actions\Action`

### Form/Schema Changes
RelationManagers use:
- `Filament\Schemas\Schema` instead of `Filament\Forms\Form`
- `->components([...])` instead of `->schema([...])`

## Commands
- `composer install`: Install PHP dependencies
- `npm install`: Install Node.js dependencies
- `php artisan migrate`: Run database migrations
- `php artisan db:seed`: Seed the database
- `npm run dev`: Start Vite development server
- `npm run build`: Build assets for production
- `composer dev`: Run all development services concurrently

## Geographic Data Import
- `php artisan psgc:import`: Import all PSGC geographic data from GitLab API
- `php artisan psgc:import --type=regions`: Import specific type
- `php artisan psgc:import --clear`: Clear existing data before import

## Data Sources
- **PSGC GitLab API**: https://psgc.gitlab.io/api/
  - Regions: 17
  - Provinces: 81
  - Cities: 146
  - Municipalities: 1,488
  - Barangays: 42,046