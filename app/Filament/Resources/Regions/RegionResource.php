<?php

namespace App\Filament\Resources\Regions;

use App\Filament\Resources\Regions\Pages\CreateRegion;
use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\Regions\Pages\ListRegions;
use App\Filament\Resources\Regions\Tables\RegionsTable;
use App\Filament\Resources\Regions\RelationManagers\ProvincesRelationManager;
use App\Models\Region;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-americas';

    protected static string | \UnitEnum | null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Regions';

    protected static ?string $pluralModelLabel = 'Regions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make()
                    ->schema([
                        Section::make('Region Information')
                    ->description('Basic region details and identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Region Code')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., 01, 02, NCR')
                                    ->helperText('Official region code'),
                                
                                TextInput::make('name')
                                    ->label('Region Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., National Capital Region'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('abbreviation')
                                    ->label('Abbreviation')
                                    ->maxLength(20)
                                    ->placeholder('e.g., NCR')
                                    ->helperText('Common abbreviation for the region'),
                                
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(9999)
                                    ->helperText('Order for display in lists (lower numbers appear first)'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Toggle to activate or deactivate this region')
                            ->default(true),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return RegionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProvincesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'create' => CreateRegion::route('/create'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'uuid';
    }
}
