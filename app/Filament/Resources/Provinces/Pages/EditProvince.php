<?php

namespace App\Filament\Resources\Provinces\Pages;

use App\Filament\Resources\Provinces\ProvinceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProvince extends EditRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_region')
                ->label('View Region')
                ->icon('heroicon-o-arrow-up-circle')
                ->url(fn () => $this->record->region ? "/admin/regions/{$this->record->region->uuid}/edit" : null)
                ->visible(fn () => $this->record->region)
                ->color('gray'),
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();

        if ($this->record->region) {
            array_splice($breadcrumbs, -1, 0, [
                "/admin/regions/{$this->record->region->uuid}/edit" => "Region: {$this->record->region->name}",
            ]);
        }

        return $breadcrumbs;
    }
}
