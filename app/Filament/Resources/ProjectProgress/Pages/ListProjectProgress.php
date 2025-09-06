<?php

namespace App\Filament\Resources\ProjectProgress\Pages;

use App\Filament\Resources\ProjectProgress\ProjectProgressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjectProgress extends ListRecords
{
    protected static string $resource = ProjectProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
