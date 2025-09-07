<?php

namespace App\Filament\Resources\SourceOfFunds;

use App\Filament\Resources\SourceOfFunds\Pages\CreateSourceOfFund;
use App\Filament\Resources\SourceOfFunds\Pages\EditSourceOfFund;
use App\Filament\Resources\SourceOfFunds\Pages\ListSourceOfFunds;
use App\Filament\Resources\SourceOfFunds\Tables\SourceOfFundsTable;
use App\Models\SourceOfFund;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SourceOfFundResource extends Resource
{
    protected static ?string $model = SourceOfFund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make()
                    ->schema([
                        Section::make('Basic Information')
                    ->description('Core funding source details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Fund Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., General Appropriations Act'),
                                
                                TextInput::make('name_abbreviation')
                                    ->label('Abbreviation')
                                    ->maxLength(20)
                                    ->placeholder('e.g., GAA'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label('Fund Type')
                                    ->options([
                                        'national' => 'National',
                                        'local' => 'Local',
                                        'international' => 'International',
                                        'private' => 'Private',
                                    ])
                                    ->placeholder('Select fund type')
                                    ->helperText('Classification of the funding source'),
                                
                                TextInput::make('fiscal_year')
                                    ->label('Fiscal Year')
                                    ->maxLength(20)
                                    ->placeholder('e.g., 2024, 2024-2025')
                                    ->helperText('Applicable fiscal year or period'),
                            ]),

                        FileUpload::make('logo_url')
                            ->label('Fund Logo')
                            ->image()
                            ->disk('public')
                            ->directory('fund-logos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Upload funding source logo (max 2MB)'),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of the funding source'),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Toggle to activate or deactivate this funding source')
                            ->default(true),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return SourceOfFundsTable::configure($table);
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
            'index' => ListSourceOfFunds::route('/'),
            'create' => CreateSourceOfFund::route('/create'),
            'edit' => EditSourceOfFund::route('/{record}/edit'),
        ];
    }
}
