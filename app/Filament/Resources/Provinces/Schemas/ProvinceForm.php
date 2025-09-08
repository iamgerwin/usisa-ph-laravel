<?php

namespace App\Filament\Resources\Provinces\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Region;

class ProvinceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
