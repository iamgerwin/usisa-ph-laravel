<?php

namespace App\Filament\Resources\Barangays;

use App\Filament\Resources\Barangays\Pages\CreateBarangay;
use App\Filament\Resources\Barangays\Pages\EditBarangay;
use App\Filament\Resources\Barangays\Pages\ListBarangays;
use App\Filament\Resources\Barangays\Schemas\BarangayForm;
use App\Filament\Resources\Barangays\Tables\BarangaysTable;
use App\Models\Barangay;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BarangayResource extends Resource
{
    protected static ?string $model = Barangay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BarangayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BarangaysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBarangays::route('/'),
            'create' => CreateBarangay::route('/create'),
            'edit' => EditBarangay::route('/{record}/edit'),
        ];
    }
}
