<?php

namespace App\Filament\Resources\ImplementingOffices\Pages;

use App\Filament\Resources\ImplementingOffices\ImplementingOfficeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImplementingOffice extends EditRecord
{
    protected static string $resource = ImplementingOfficeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
