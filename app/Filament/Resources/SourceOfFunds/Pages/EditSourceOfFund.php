<?php

namespace App\Filament\Resources\SourceOfFunds\Pages;

use App\Filament\Resources\SourceOfFunds\SourceOfFundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSourceOfFund extends EditRecord
{
    protected static string $resource = SourceOfFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
