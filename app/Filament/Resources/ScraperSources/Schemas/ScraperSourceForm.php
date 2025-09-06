<?php

namespace App\Filament\Resources\ScraperSources\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ScraperSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->helperText('Unique identifier for the source (e.g., dime)'),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Display name for the source'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Enable or disable this scraper source'),
                                TextInput::make('version')
                                    ->maxLength(20)
                                    ->default('1.0.0')
                                    ->helperText('Version of the scraper configuration'),
                            ]),
                    ]),
                
                Section::make('API Configuration')
                    ->schema([
                        TextInput::make('base_url')
                            ->required()
                            ->url()
                            ->maxLength(500)
                            ->helperText('Base URL of the API (e.g., https://api.dime.gov.ph)'),
                        TextInput::make('endpoint_pattern')
                            ->maxLength(500)
                            ->placeholder('/api/projects/{id}')
                            ->helperText('Endpoint pattern with {id} placeholder'),
                        Select::make('scraper_class')
                            ->options([
                                'App\\Services\\Scrapers\\DimeScraperStrategy' => 'DIME Scraper',
                            ])
                            ->required()
                            ->helperText('Select the scraper strategy to use'),
                    ]),
                
                Section::make('Rate Limiting & Performance')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('rate_limit')
                                    ->numeric()
                                    ->default(10)
                                    ->suffix('requests/second')
                                    ->helperText('Maximum requests per second'),
                                TextInput::make('timeout')
                                    ->numeric()
                                    ->default(30)
                                    ->suffix('seconds')
                                    ->helperText('Request timeout in seconds'),
                                TextInput::make('retry_attempts')
                                    ->numeric()
                                    ->default(3)
                                    ->helperText('Number of retry attempts for failed requests'),
                            ]),
                    ]),
                
                Section::make('Advanced Configuration')
                    ->schema([
                        KeyValue::make('headers')
                            ->keyLabel('Header Name')
                            ->valueLabel('Header Value')
                            ->addActionLabel('Add Header')
                            ->helperText('Additional HTTP headers to send with requests'),
                        KeyValue::make('field_mapping')
                            ->keyLabel('Target Field')
                            ->valueLabel('Source Field')
                            ->addActionLabel('Add Mapping')
                            ->helperText('Map API response fields to model fields'),
                        KeyValue::make('metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Metadata')
                            ->helperText('Additional configuration metadata'),
                    ])
                    ->collapsible(),
            ]);
    }
}
