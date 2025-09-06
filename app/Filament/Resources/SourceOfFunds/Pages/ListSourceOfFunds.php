<?php

namespace App\Filament\Resources\SourceOfFunds\Pages;

use App\Filament\Resources\SourceOfFunds\SourceOfFundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSourceOfFunds extends ListRecords
{
    protected static string $resource = SourceOfFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
