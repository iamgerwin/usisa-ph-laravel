<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Contractor;
use App\Models\ImplementingOffice;
use App\Models\Program;
use App\Models\Province;
use App\Models\Region;
use App\Models\SourceOfFund;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Project Details')
                    ->tabs([
                        Tab::make('Basic Information')
                            ->schema([
                                Section::make('Project Details')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('project_name')
                                                    ->label('Project Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (Set $set, ?string $state) => 
                                                        $set('slug', Str::slug($state))),
                                                
                                                TextInput::make('project_code')
                                                    ->label('Project Code')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(50),
                                            ]),
                                        
                                        TextInput::make('slug')
                                            ->label('URL Slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                        
                                        RichEditor::make('description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->maxLength(65535),
                                        
                                        TextInput::make('project_image_url')
                                            ->label('Project Image URL')
                                            ->url()
                                            ->maxLength(500),
                                        
                                        Select::make('program_id')
                                            ->label('Program')
                                            ->relationship('program', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ]),
                            ]),
                        
                        Tab::make('Location')
                            ->schema([
                                Section::make('Geographic Location')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('region_id')
                                                    ->label('Region')
                                                    ->options(Region::all()->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set) => $set('province_id', null)),
                                                
                                                Select::make('province_id')
                                                    ->label('Province')
                                                    ->options(fn (Get $get): array => 
                                                        Province::where('region_id', $get('region_id'))
                                                            ->pluck('name', 'id')
                                                            ->toArray())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set) => $set('city_id', null)),
                                                
                                                Select::make('city_id')
                                                    ->label('City/Municipality')
                                                    ->options(fn (Get $get): array => 
                                                        City::where('province_id', $get('province_id'))
                                                            ->pluck('name', 'id')
                                                            ->toArray())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set) => $set('barangay_id', null)),
                                                
                                                Select::make('barangay_id')
                                                    ->label('Barangay')
                                                    ->options(fn (Get $get): array => 
                                                        Barangay::where('city_id', $get('city_id'))
                                                            ->pluck('name', 'id')
                                                            ->toArray())
                                                    ->searchable()
                                                    ->preload(),
                                            ]),
                                        
                                        TextInput::make('street_address')
                                            ->label('Street Address')
                                            ->maxLength(255),
                                        
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('zip_code')
                                                    ->label('ZIP Code')
                                                    ->maxLength(10),
                                                
                                                TextInput::make('latitude')
                                                    ->label('Latitude')
                                                    ->numeric()
                                                    ->step(0.00000001),
                                                
                                                TextInput::make('longitude')
                                                    ->label('Longitude')
                                                    ->numeric()
                                                    ->step(0.00000001),
                                            ]),
                                    ]),
                            ]),
                        
                        Tab::make('Budget & Schedule')
                            ->schema([
                                Section::make('Budget Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('cost')
                                                    ->label('Project Cost')
                                                    ->numeric()
                                                    ->prefix('₱')
                                                    ->step(0.01),
                                                
                                                TextInput::make('utilized_amount')
                                                    ->label('Utilized Amount')
                                                    ->numeric()
                                                    ->prefix('₱')
                                                    ->step(0.01),
                                                
                                                DatePicker::make('last_updated_project_cost')
                                                    ->label('Last Updated Project Cost'),
                                            ]),
                                    ]),
                                
                                Section::make('Schedule')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('date_started')
                                                    ->label('Planned Start Date'),
                                                
                                                DatePicker::make('actual_date_started')
                                                    ->label('Actual Start Date'),
                                                
                                                DatePicker::make('contract_completion_date')
                                                    ->label('Contract Completion Date'),
                                                
                                                DatePicker::make('actual_contract_completion_date')
                                                    ->label('Actual Completion Date'),
                                                
                                                DatePicker::make('as_of_date')
                                                    ->label('As of Date'),
                                            ]),
                                    ]),
                            ]),
                        
                        Tab::make('Relationships')
                            ->schema([
                                Section::make('Implementing Offices')
                                    ->schema([
                                        Select::make('implementingOffices')
                                            ->label('Implementing Offices')
                                            ->relationship('implementingOffices', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                    ]),
                                
                                Section::make('Funding Sources')
                                    ->schema([
                                        Select::make('sourceOfFunds')
                                            ->label('Source of Funds')
                                            ->relationship('sourceOfFunds', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                    ]),
                                
                                Section::make('Contractors')
                                    ->schema([
                                        Select::make('contractors')
                                            ->label('Contractors')
                                            ->relationship('contractors', 'company_name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ]),
                        
                        Tab::make('Status & Metadata')
                            ->schema([
                                Section::make('Status')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('status')
                                                    ->label('Project Status')
                                                    ->options([
                                                        'draft' => 'Draft',
                                                        'pending' => 'Pending',
                                                        'active' => 'Active',
                                                        'on_hold' => 'On Hold',
                                                        'completed' => 'Completed',
                                                        'cancelled' => 'Cancelled',
                                                    ])
                                                    ->default('draft')
                                                    ->required(),
                                                
                                                Select::make('publication_status')
                                                    ->label('Publication Status')
                                                    ->options([
                                                        'draft' => 'Draft',
                                                        'published' => 'Published',
                                                        'archived' => 'Archived',
                                                    ])
                                                    ->default('draft')
                                                    ->required(),
                                            ]),
                                        
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Is Active')
                                                    ->default(true),
                                                
                                                Toggle::make('is_featured')
                                                    ->label('Is Featured')
                                                    ->default(false),
                                                
                                                TextInput::make('updates_count')
                                                    ->label('Updates Count')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->disabled(),
                                            ]),
                                    ]),
                                
                                Section::make('Additional Metadata')
                                    ->schema([
                                        KeyValue::make('metadata')
                                            ->label('Metadata')
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addButtonLabel('Add metadata'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}