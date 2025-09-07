<?php

namespace App\Filament\Resources\ProjectProgress;

use App\Filament\Resources\ProjectProgress\Pages\CreateProjectProgress;
use App\Filament\Resources\ProjectProgress\Pages\EditProjectProgress;
use App\Filament\Resources\ProjectProgress\Pages\ListProjectProgress;
use App\Filament\Resources\ProjectProgress\Tables\ProjectProgressTable;
use App\Models\ProjectProgress;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectProgressResource extends Resource
{
    protected static ?string $model = ProjectProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Progress Information')
                    ->description('Record project progress details')
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Select a project'),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('progress_date')
                                    ->label('Progress Date')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now()),
                                
                                Select::make('status')
                                    ->label('Status')
                                    ->required()
                                    ->options([
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'delayed' => 'Delayed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('in_progress'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('progress_percentage')
                                    ->label('Overall Progress (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->placeholder('0.00'),
                                
                                TextInput::make('physical_progress')
                                    ->label('Physical Progress (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->placeholder('0.00'),
                                
                                TextInput::make('financial_progress')
                                    ->label('Financial Progress (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->placeholder('0.00'),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe the progress made during this period'),

                        Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Additional notes or observations'),

                        TextInput::make('reported_by')
                            ->label('Reported By')
                            ->maxLength(255)
                            ->placeholder('Name of person reporting progress'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ProjectProgressTable::configure($table);
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
            'index' => ListProjectProgress::route('/'),
            'create' => CreateProjectProgress::route('/create'),
            'edit' => EditProjectProgress::route('/{record}/edit'),
        ];
    }
}
