<?php

namespace App\Filament\Resources\Regions\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ProvincesRelationManager extends RelationManager
{
    protected static string $relationship = 'provinces';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('abbreviation')
                    ->maxLength(20),
                Forms\Components\TextInput::make('income_class')
                    ->maxLength(20),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('abbreviation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('income_class')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1st' => 'success',
                        '2nd' => 'info',
                        '3rd' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('cities_count')
                    ->label('Cities/Municipalities')
                    ->counts('cities')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Projects')
                    ->counts('projects')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                Action::make('view_cities')
                    ->label('View Cities')
                    ->icon('heroicon-o-building-library')
                    ->url(fn ($record) => "/admin/provinces/{$record->uuid}/edit")
                    ->color('info'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}