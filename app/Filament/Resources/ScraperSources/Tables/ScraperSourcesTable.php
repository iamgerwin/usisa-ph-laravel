<?php

namespace App\Filament\Resources\ScraperSources\Tables;

use App\Models\ScraperSource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BoolColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class ScraperSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                BoolColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('base_url')
                    ->limit(30)
                    ->tooltip(fn (ScraperSource $record): string => $record->base_url),
                TextColumn::make('rate_limit')
                    ->numeric()
                    ->suffix('/sec')
                    ->sortable(),
                TextColumn::make('jobs_count')
                    ->counts('jobs')
                    ->label('Total Jobs'),
                TextColumn::make('latestJob.status')
                    ->label('Latest Job')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'running' => 'warning',
                        'failed' => 'danger',
                        'paused' => 'gray',
                        default => 'secondary',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('run')
                    ->label('Run Scraper')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Run Scraper')
                    ->modalDescription('Start a new scraping job for this source?')
                    ->modalSubmitActionLabel('Start Scraping')
                    ->action(function (ScraperSource $record) {
                        Artisan::call('scrape:data', [
                            '--source' => $record->code,
                            '--start' => 1,
                            '--end' => 100,
                        ]);
                    })
                    ->visible(fn (ScraperSource $record): bool => $record->is_active),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
