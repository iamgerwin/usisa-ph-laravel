<?php

namespace App\Filament\Resources\ScraperJobs;

use App\Enums\ScraperJobStatus;
use App\Filament\Resources\ScraperJobs\Pages\CreateScraperJob;
use App\Filament\Resources\ScraperJobs\Pages\EditScraperJob;
use App\Filament\Resources\ScraperJobs\Pages\ListScraperJobs;
use App\Filament\Resources\ScraperJobs\Tables\ScraperJobsTable;
use App\Models\ScraperJob;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScraperJobResource extends Resource
{
    protected static ?string $model = ScraperJob::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Scraper Jobs';

    protected static ?string $pluralModelLabel = 'Scraper Jobs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make()
                    ->schema([
                        Section::make('Job Configuration')
                    ->description('Configure the scraping job parameters')
                    ->schema([
                        Select::make('source_id')
                            ->label('Source')
                            ->relationship('source', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Select a scraper source')
                            ->disabled(fn ($operation) => $operation === 'edit'),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('start_id')
                                    ->label('Start ID')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->disabled(fn ($operation) => $operation === 'edit'),
                                
                                TextInput::make('end_id')
                                    ->label('End ID')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->rules(['gte:start_id'])
                                    ->disabled(fn ($operation) => $operation === 'edit'),
                                
                                TextInput::make('chunk_size')
                                    ->label('Chunk Size')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(1000)
                                    ->default(100)
                                    ->helperText('Number of items to process at once')
                                    ->disabled(fn ($operation) => $operation === 'edit'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('triggered_by')
                                    ->label('Triggered By')
                                    ->maxLength(255)
                                    ->placeholder('Username or system that triggered this job')
                                    ->default(auth()->user()?->name ?? 'System')
                                    ->disabled(fn ($operation) => $operation === 'edit'),
                                
                                Select::make('status')
                                    ->label('Status')
                                    ->options(ScraperJobStatus::options())
                                    ->default(ScraperJobStatus::PENDING->value)
                                    ->disabled(fn ($operation) => $operation === 'create'),
                            ]),
                    ]),

                Section::make('Progress Information')
                    ->description('Current job progress and statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_id')
                                    ->label('Current ID')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('success_count')
                                    ->label('Success Count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                                
                                TextInput::make('error_count')
                                    ->label('Error Count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('skip_count')
                                    ->label('Skip Count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                                
                                TextInput::make('update_count')
                                    ->label('Update Count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                                
                                TextInput::make('create_count')
                                    ->label('Create Count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                            ]),
                    ])
                    ->visibleOn('edit')
                    ->collapsed(),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Additional notes about this job'),
                    ])
                    ->collapsed(),
                
                // Hidden fields for internal use
                Hidden::make('started_at'),
                Hidden::make('completed_at'),
                Hidden::make('stats'),
                Hidden::make('errors'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ScraperJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScraperJobs::route('/'),
            'create' => CreateScraperJob::route('/create'),
            'edit' => EditScraperJob::route('/{record}/edit'),
        ];
    }
}
