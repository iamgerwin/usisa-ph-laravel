<?php

namespace App\Filament\Resources\Contractors\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContractorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Core contractor details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Contractor Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., ABC Construction Inc.'),
                                
                                TextInput::make('name_abbreviation')
                                    ->label('Abbreviation')
                                    ->maxLength(20)
                                    ->placeholder('e.g., ABC'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('contractor_type')
                                    ->label('Contractor Type')
                                    ->options([
                                        'General Contractor' => 'General Contractor',
                                        'Subcontractor' => 'Subcontractor',
                                        'Consultant' => 'Consultant',
                                        'Supplier' => 'Supplier',
                                        'Other' => 'Other',
                                    ])
                                    ->placeholder('Select contractor type'),
                                
                                TextInput::make('license_number')
                                    ->label('License Number')
                                    ->maxLength(50)
                                    ->placeholder('e.g., LIC-12345'),
                            ]),

                        FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('contractor-logos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Upload contractor logo (max 2MB)'),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of the contractor'),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('contractor@company.com'),
                                
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('+63 912 345 6789'),
                            ]),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.company.com'),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Complete business address'),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('license_expiry')
                                    ->label('License Expiry')
                                    ->helperText('When does the license expire?'),
                                
                                Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->helperText('Toggle to activate or deactivate contractor')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
