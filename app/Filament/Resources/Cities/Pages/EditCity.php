<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_province')
                ->label('View Province')
                ->icon('heroicon-o-arrow-up-circle')
                ->url(fn () => $this->record->province ? "/admin/provinces/{$this->record->province->uuid}/edit" : null)
                ->visible(fn () => $this->record->province)
                ->color('gray'),
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();

        if ($this->record->province) {
            $region = $this->record->province->region;

            if ($region) {
                array_splice($breadcrumbs, -1, 0, [
                    "/admin/regions/{$region->uuid}/edit" => "Region: {$region->name}",
                    "/admin/provinces/{$this->record->province->uuid}/edit" => "Province: {$this->record->province->name}",
                ]);
            } else {
                array_splice($breadcrumbs, -1, 0, [
                    "/admin/provinces/{$this->record->province->uuid}/edit" => "Province: {$this->record->province->name}",
                ]);
            }
        }

        return $breadcrumbs;
    }
}
