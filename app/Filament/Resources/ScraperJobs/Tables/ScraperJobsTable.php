<?php

namespace App\Filament\Resources\ScraperJobs\Tables;

use App\Enums\ScraperJobStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ProgressBarColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\ScraperSource;

class ScraperJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name')
                    ->label('Source')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('start_id')
                    ->label('Start ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('end_id')
                    ->label('End ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_id')
                    ->label('Current ID')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A'),
                ProgressBarColumn::make('progress_percentage')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        return $record->progress_percentage;
                    })
                    ->color('success'),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (ScraperJobStatus $state) => $state->label())
                    ->color(fn (ScraperJobStatus $state) => $state->color()),
                TextColumn::make('chunk_size')
                    ->label('Chunk Size')
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('success_count')
                    ->label('Success')
                    ->numeric()
                    ->color('success')
                    ->toggleable(),
                TextColumn::make('error_count')
                    ->label('Errors')
                    ->numeric()
                    ->color('danger')
                    ->toggleable(),
                TextColumn::make('skip_count')
                    ->label('Skipped')
                    ->numeric()
                    ->color('warning')
                    ->toggleable(),
                TextColumn::make('total_processed')
                    ->label('Total Processed')
                    ->getStateUsing(fn ($record) => $record->total_processed)
                    ->numeric()
                    ->badge()
                    ->color('info'),
                TextColumn::make('formatted_duration')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->formatted_duration)
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('triggered_by')
                    ->label('Triggered By')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('N/A')
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
                SelectFilter::make('source_id')
                    ->label('Source')
                    ->relationship('source', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ScraperJobStatus::options()),
                DateFilter::make('started_at')
                    ->label('Started Date'),
                DateFilter::make('completed_at')
                    ->label('Completed Date'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable(['triggered_by', 'notes'])
            ->poll('10s');
    }
}
