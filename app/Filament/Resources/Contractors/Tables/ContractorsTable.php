<?php

namespace App\Filament\Resources\Contractors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContractorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('/images/contractor-placeholder.png')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Contractor Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('name_abbreviation')
                    ->label('Abbreviation')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('contractor_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('license_number')
                    ->label('License No.')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('N/A'),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('N/A'),
                TextColumn::make('projects_count')
                    ->label('Projects')
                    ->counts('projects')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('contractor_type')
                    ->label('Contractor Type')
                    ->options([
                        'General Contractor' => 'General Contractor',
                        'Subcontractor' => 'Subcontractor',
                        'Consultant' => 'Consultant',
                        'Supplier' => 'Supplier',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->searchable(['name', 'name_abbreviation', 'email', 'phone', 'license_number']);
    }
}
