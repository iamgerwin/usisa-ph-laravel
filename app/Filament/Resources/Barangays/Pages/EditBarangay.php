<?php

namespace App\Filament\Resources\Barangays\Pages;

use App\Filament\Resources\Barangays\BarangayResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditBarangay extends EditRecord
{
    protected static string $resource = BarangayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_city')
                ->label('View City/Municipality')
                ->icon('heroicon-o-arrow-up-circle')
                ->url(fn () => $this->record->city ? "/admin/cities/{$this->record->city->uuid}/edit" : null)
                ->visible(fn () => $this->record->city)
                ->color('gray'),
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();

        if ($this->record->city) {
            $province = $this->record->city->province;

            if ($province) {
                $region = $province->region;

                if ($region) {
                    array_splice($breadcrumbs, -1, 0, [
                        "/admin/regions/{$region->uuid}/edit" => "Region: {$region->name}",
                        "/admin/provinces/{$province->uuid}/edit" => "Province: {$province->name}",
                        "/admin/cities/{$this->record->city->uuid}/edit" => ucfirst($this->record->city->type) . ": {$this->record->city->name}",
                    ]);
                } else {
                    array_splice($breadcrumbs, -1, 0, [
                        "/admin/provinces/{$province->uuid}/edit" => "Province: {$province->name}",
                        "/admin/cities/{$this->record->city->uuid}/edit" => ucfirst($this->record->city->type) . ": {$this->record->city->name}",
                    ]);
                }
            } else {
                array_splice($breadcrumbs, -1, 0, [
                    "/admin/cities/{$this->record->city->uuid}/edit" => ucfirst($this->record->city->type) . ": {$this->record->city->name}",
                ]);
            }
        }

        return $breadcrumbs;
    }
}
