<?php

namespace App\Filament\Resources\ProjectProgress;

use App\Filament\Resources\ProjectProgress\Pages\CreateProjectProgress;
use App\Filament\Resources\ProjectProgress\Pages\EditProjectProgress;
use App\Filament\Resources\ProjectProgress\Pages\ListProjectProgress;
use App\Filament\Resources\ProjectProgress\Schemas\ProjectProgressForm;
use App\Filament\Resources\ProjectProgress\Tables\ProjectProgressTable;
use App\Models\ProjectProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectProgressResource extends Resource
{
    protected static ?string $model = ProjectProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProjectProgressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectProgressTable::configure($table);
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
            'index' => ListProjectProgress::route('/'),
            'create' => CreateProjectProgress::route('/create'),
            'edit' => EditProjectProgress::route('/{record}/edit'),
        ];
    }
}
