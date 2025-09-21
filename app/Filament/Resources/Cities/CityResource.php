<?php

namespace App\Filament\Resources\Cities;

use App\Filament\Resources\Cities\Pages\CreateCity;
use App\Filament\Resources\Cities\Pages\EditCity;
use App\Filament\Resources\Cities\Pages\ListCities;
use App\Filament\Resources\Cities\Tables\CitiesTable;
use App\Filament\Resources\Cities\RelationManagers\BarangaysRelationManager;
use App\Models\City;
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

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Cities/Municipalities';

    protected static ?string $pluralModelLabel = 'Cities/Municipalities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make()
                    ->schema([
                        Section::make('City/Municipality Information')
                    ->description('Basic information about the city or municipality')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('PSGC Code')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., 012801')
                                    ->helperText('Philippine Standard Geographic Code'),
                                
                                TextInput::make('name')
                                    ->label('City/Municipality Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Makati City'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('province_id')
                                    ->label('Province')
                                    ->relationship('province', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a province'),
                                
                                Select::make('type')
                                    ->label('Type')
                                    ->required()
                                    ->options([
                                        'city' => 'City',
                                        'municipality' => 'Municipality',
                                    ])
                                    ->default('municipality')
                                    ->helperText('Whether this is a city or municipality'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('zip_code')
                                    ->label('Zip Code')
                                    ->maxLength(10)
                                    ->placeholder('e.g., 1200')
                                    ->helperText('Postal code (optional)'),
                                
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(9999)
                                    ->helperText('Order for display in lists'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Toggle to activate or deactivate this city/municipality')
                            ->default(true),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return CitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BarangaysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCities::route('/'),
            'create' => CreateCity::route('/create'),
            'edit' => EditCity::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'uuid';
    }
}
