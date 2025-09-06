<?php

namespace App\Filament\Resources\ScraperSources\Pages;

use App\Filament\Resources\ScraperSources\ScraperSourceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;

class EditScraperSource extends EditRecord
{
    protected static string $resource = ScraperSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label('Run Scraper')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Run Scraper')
                ->modalDescription('Start a new scraping job for this source?')
                ->modalSubmitActionLabel('Start Scraping')
                ->action(function () {
                    Artisan::call('scrape:data', [
                        '--source' => $this->record->code,
                        '--start' => 1,
                        '--end' => 100,
                        '--dry-run' => true, // For safety during testing
                    ]);
                    
                    Notification::make()
                        ->title('Scraper started')
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => $this->record->is_active),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
