<?php

namespace App\Filament\Resources\ProjectProgress\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Project;

class ProjectProgressTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.title')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(40),
                TextColumn::make('progress_date')
                    ->label('Progress Date')
                    ->date()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('progress_percentage')
                    ->label('Progress %')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('physical_progress')
                    ->label('Physical %')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('financial_progress')
                    ->label('Financial %')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'delayed' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reported_by')
                    ->label('Reported By')
                    ->searchable()
                    ->toggleable(),
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
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'project_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'delayed' => 'Delayed',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('progress_date')
                    ->form([
                        DatePicker::make('progress_date_from')
                            ->label('From'),
                        DatePicker::make('progress_date_to')
                            ->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['progress_date_from'],
                                fn ($query, $date) => $query->whereDate('progress_date', '>=', $date)
                            )
                            ->when(
                                $data['progress_date_to'],
                                fn ($query, $date) => $query->whereDate('progress_date', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('progress_date', 'desc');
    }
}
