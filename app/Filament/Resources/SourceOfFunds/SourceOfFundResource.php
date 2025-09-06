<?php

namespace App\Filament\Resources\SourceOfFunds;

use App\Filament\Resources\SourceOfFunds\Pages\CreateSourceOfFund;
use App\Filament\Resources\SourceOfFunds\Pages\EditSourceOfFund;
use App\Filament\Resources\SourceOfFunds\Pages\ListSourceOfFunds;
use App\Filament\Resources\SourceOfFunds\Schemas\SourceOfFundForm;
use App\Filament\Resources\SourceOfFunds\Tables\SourceOfFundsTable;
use App\Models\SourceOfFund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SourceOfFundResource extends Resource
{
    protected static ?string $model = SourceOfFund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SourceOfFundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SourceOfFundsTable::configure($table);
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
            'index' => ListSourceOfFunds::route('/'),
            'create' => CreateSourceOfFund::route('/create'),
            'edit' => EditSourceOfFund::route('/{record}/edit'),
        ];
    }
}
