<?php

namespace App\Filament\Resources\Provinces\RelationManagers;

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

class CitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'cities';

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
                Forms\Components\Select::make('type')
                    ->options([
                        'city' => 'City',
                        'municipality' => 'Municipality',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('zip_code')
                    ->maxLength(10),
                Forms\Components\TextInput::make('city_class')
                    ->maxLength(20),
                Forms\Components\TextInput::make('income_class')
                    ->maxLength(20),
                Forms\Components\Toggle::make('is_capital')
                    ->label('Provincial Capital'),
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
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'city' => 'success',
                        'municipality' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city_class')
                    ->label('Class')
                    ->badge(),
                Tables\Columns\TextColumn::make('income_class')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1st' => 'success',
                        '2nd' => 'info',
                        '3rd' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_capital')
                    ->label('Capital')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),
                Tables\Columns\TextColumn::make('barangays_count')
                    ->label('Barangays')
                    ->counts('barangays')
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'city' => 'City',
                        'municipality' => 'Municipality',
                    ]),
                Tables\Filters\TernaryFilter::make('is_capital')
                    ->label('Provincial Capital'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                Action::make('view_barangays')
                    ->label('View Barangays')
                    ->icon('heroicon-o-home-modern')
                    ->url(fn ($record) => "/admin/cities/{$record->uuid}/edit")
                    ->color('info'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('type')
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->collapsible(),
            ]);
    }
}