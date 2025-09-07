<?php

namespace App\Filament\Resources\ScraperSources\Pages;

use App\Filament\Resources\ScraperSources\ScraperSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScraperSources extends ListRecords
{
    protected static string $resource = ScraperSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
