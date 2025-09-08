<?php

namespace App\Filament\Resources\Barangays\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\City;

class BarangayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Barangay Information')
                    ->description('Basic information about the barangay')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('PSGC Code')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., 012801001')
                                    ->helperText('Philippine Standard Geographic Code for this barangay'),
                                
                                TextInput::make('name')
                                    ->label('Barangay Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Poblacion'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('city_id')
                                    ->label('City/Municipality')
                                    ->relationship('city', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a city or municipality'),
                                
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
                            ->helperText('Toggle to activate or deactivate this barangay')
                            ->default(true),
                    ]),
            ]);
    }
}
