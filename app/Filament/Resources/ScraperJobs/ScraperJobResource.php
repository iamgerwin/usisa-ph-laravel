<?php

namespace App\Filament\Resources\ScraperJobs;

use App\Filament\Resources\ScraperJobs\Pages\CreateScraperJob;
use App\Filament\Resources\ScraperJobs\Pages\EditScraperJob;
use App\Filament\Resources\ScraperJobs\Pages\ListScraperJobs;
use App\Filament\Resources\ScraperJobs\Schemas\ScraperJobForm;
use App\Filament\Resources\ScraperJobs\Tables\ScraperJobsTable;
use App\Models\ScraperJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScraperJobResource extends Resource
{
    protected static ?string $model = ScraperJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ScraperJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScraperJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScraperJobs::route('/'),
            'create' => CreateScraperJob::route('/create'),
            'edit' => EditScraperJob::route('/{record}/edit'),
        ];
    }
}
