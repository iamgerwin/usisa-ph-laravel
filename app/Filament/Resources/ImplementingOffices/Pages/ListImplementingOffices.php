<?php

namespace App\Filament\Resources\ImplementingOffices\Pages;

use App\Filament\Resources\ImplementingOffices\ImplementingOfficeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImplementingOffices extends ListRecords
{
    protected static string $resource = ImplementingOfficeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
