<?php

namespace App\Filament\Resources\ProjectProgress\Pages;

use App\Filament\Resources\ProjectProgress\ProjectProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProjectProgress extends EditRecord
{
    protected static string $resource = ProjectProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
