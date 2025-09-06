<?php

namespace App\Filament\Resources\ScraperSources\Pages;

use App\Filament\Resources\ScraperSources\ScraperSourceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditScraperSource extends EditRecord
{
    protected static string $resource = ScraperSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
