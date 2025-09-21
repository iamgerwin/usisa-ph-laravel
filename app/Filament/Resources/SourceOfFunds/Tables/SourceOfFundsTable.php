<?php

namespace App\Filament\Resources\SourceOfFunds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SourceOfFundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('/images/fund-placeholder.png')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Fund Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('name_abbreviation')
                    ->label('Abbreviation')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'national' => 'success',
                        'local' => 'info',
                        'international' => 'warning',
                        'private' => 'primary',
                        default => 'gray',
                    })
                    ->placeholder('N/A'),
                TextColumn::make('fiscal_year')
                    ->label('Fiscal Year')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    })
                    ->placeholder('No description')
                    ->toggleable(),
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
                SelectFilter::make('type')
                    ->label('Fund Type')
                    ->options([
                        'national' => 'National',
                        'local' => 'Local',
                        'international' => 'International',
                        'private' => 'Private',
                    ]),
                SelectFilter::make('fiscal_year')
                    ->label('Fiscal Year')
                    ->options(function () {
                        $years = range(date('Y') - 10, date('Y') + 5);
                        return array_combine($years, $years);
                    }),
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
            ->defaultSort('name');
    }
}
