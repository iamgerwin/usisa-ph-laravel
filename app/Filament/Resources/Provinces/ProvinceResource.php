<?php

namespace App\Filament\Resources\Provinces;

use App\Filament\Resources\Provinces\Pages\CreateProvince;
use App\Filament\Resources\Provinces\Pages\EditProvince;
use App\Filament\Resources\Provinces\Pages\ListProvinces;
use App\Filament\Resources\Provinces\Tables\ProvincesTable;
use App\Filament\Resources\Provinces\RelationManagers\CitiesRelationManager;
use App\Models\Province;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string | \UnitEnum | null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Provinces';

    protected static ?string $pluralModelLabel = 'Provinces';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make()
                    ->schema([
                        Section::make('Province Information')
                    ->description('Basic province details and identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('PSGC Code')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., 0128')
                                    ->helperText('Philippine Standard Geographic Code'),
                                
                                TextInput::make('name')
                                    ->label('Province Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Metro Manila'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('region_id')
                                    ->label('Region')
                                    ->relationship('region', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a region'),
                                
                                TextInput::make('abbreviation')
                                    ->label('Abbreviation')
                                    ->maxLength(20)
                                    ->placeholder('e.g., NCR')
                                    ->helperText('Common abbreviation for the province'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(9999)
                                    ->helperText('Order for display in lists (lower numbers appear first)'),
                                
                                Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->helperText('Toggle to activate or deactivate this province')
                                    ->default(true),
                            ]),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ProvincesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProvinces::route('/'),
            'create' => CreateProvince::route('/create'),
            'edit' => EditProvince::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'uuid';
    }
}
