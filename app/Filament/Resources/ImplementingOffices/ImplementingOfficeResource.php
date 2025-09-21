<?php

namespace App\Filament\Resources\ImplementingOffices;

use App\Filament\Resources\ImplementingOffices\Pages\CreateImplementingOffice;
use App\Filament\Resources\ImplementingOffices\Pages\EditImplementingOffice;
use App\Filament\Resources\ImplementingOffices\Pages\ListImplementingOffices;
use App\Filament\Resources\ImplementingOffices\Tables\ImplementingOfficesTable;
use App\Models\ImplementingOffice;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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

class ImplementingOfficeResource extends Resource
{
    protected static ?string $model = ImplementingOffice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Implementing Offices';

    protected static ?string $pluralModelLabel = 'Implementing Offices';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make()
                    ->schema([
                        Section::make('Basic Information')
                    ->description('Core implementing office details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Office Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Department of Public Works and Highways'),
                                
                                TextInput::make('name_abbreviation')
                                    ->label('Abbreviation')
                                    ->maxLength(20)
                                    ->placeholder('e.g., DPWH'),
                            ]),

                        FileUpload::make('logo_url')
                            ->label('Office Logo')
                            ->image()
                            ->disk('public')
                            ->directory('office-logos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Upload office logo (max 2MB)'),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of the implementing office'),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('office@agency.gov.ph'),
                                
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('+63 2 123 4567'),
                            ]),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.agency.gov.ph'),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Complete office address'),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Toggle to activate or deactivate this implementing office')
                            ->default(true),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ImplementingOfficesTable::configure($table);
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
            'index' => ListImplementingOffices::route('/'),
            'create' => CreateImplementingOffice::route('/create'),
            'edit' => EditImplementingOffice::route('/{record}/edit'),
        ];
    }
}
