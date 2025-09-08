<?php

namespace App\Filament\Resources\Regions\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
