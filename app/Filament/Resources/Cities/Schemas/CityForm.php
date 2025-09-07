<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Province;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
