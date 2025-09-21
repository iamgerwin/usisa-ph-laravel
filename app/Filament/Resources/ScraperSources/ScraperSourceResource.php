<?php

namespace App\Filament\Resources\ScraperSources;

use App\Filament\Resources\ScraperSources\Pages\CreateScraperSource;
use App\Filament\Resources\ScraperSources\Pages\EditScraperSource;
use App\Filament\Resources\ScraperSources\Pages\ListScraperSources;
use App\Filament\Resources\ScraperSources\Schemas\ScraperSourceForm;
use App\Filament\Resources\ScraperSources\Tables\ScraperSourcesTable;
use App\Models\ScraperSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScraperSourceResource extends Resource
{
    protected static ?string $model = ScraperSource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Scraper Sources';

    protected static ?string $pluralModelLabel = 'Scraper Sources';

    public static function form(Schema $schema): Schema
    {
        return ScraperSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScraperSourcesTable::configure($table);
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
            'index' => ListScraperSources::route('/'),
            'create' => CreateScraperSource::route('/create'),
            'edit' => EditScraperSource::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
