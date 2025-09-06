<?php

namespace App\Filament\Resources\ScraperJobs\Pages;

use App\Filament\Resources\ScraperJobs\ScraperJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScraperJobs extends ListRecords
{
    protected static string $resource = ScraperJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
