<?php

namespace App\Filament\Resources\ImplementingOffices;

use App\Filament\Resources\ImplementingOffices\Pages\CreateImplementingOffice;
use App\Filament\Resources\ImplementingOffices\Pages\EditImplementingOffice;
use App\Filament\Resources\ImplementingOffices\Pages\ListImplementingOffices;
use App\Filament\Resources\ImplementingOffices\Schemas\ImplementingOfficeForm;
use App\Filament\Resources\ImplementingOffices\Tables\ImplementingOfficesTable;
use App\Models\ImplementingOffice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImplementingOfficeResource extends Resource
{
    protected static ?string $model = ImplementingOffice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ImplementingOfficeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImplementingOfficesTable::configure($table);
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
            'index' => ListImplementingOffices::route('/'),
            'create' => CreateImplementingOffice::route('/create'),
            'edit' => EditImplementingOffice::route('/{record}/edit'),
        ];
    }
}
