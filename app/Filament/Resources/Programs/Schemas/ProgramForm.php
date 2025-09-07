<?php

namespace App\Filament\Resources\Programs\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Program Information')
                    ->description('Basic program details and identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Program Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Build Build Build Program')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),
                                
                                TextInput::make('name_abbreviation')
                                    ->label('Abbreviation')
                                    ->maxLength(20)
                                    ->placeholder('e.g., BBB'),
                            ]),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('auto-generated from name')
                            ->helperText('URL-friendly identifier (auto-generated from name)')
                            ->rules(['alpha_dash']),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Detailed description of the program'),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Toggle to activate or deactivate this program')
                            ->default(true),
                    ]),
            ]);
    }
}
