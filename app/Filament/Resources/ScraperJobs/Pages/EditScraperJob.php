<?php

namespace App\Filament\Resources\ScraperJobs\Pages;

use App\Filament\Resources\ScraperJobs\ScraperJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScraperJob extends EditRecord
{
    protected static string $resource = ScraperJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
