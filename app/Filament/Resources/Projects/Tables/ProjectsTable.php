<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                
                TextColumn::make('project_name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('region.name')
                    ->label('Region')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('province.name')
                    ->label('Province')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('city.name')
                    ->label('City')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('cost')
                    ->label('Project Cost')
                    ->money('PHP')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('utilized_amount')
                    ->label('Utilized')
                    ->money('PHP')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'active' => 'success',
                        'on_hold' => 'danger',
                        'completed' => 'info',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                
                TextColumn::make('publication_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->label('Active'),
                
                IconColumn::make('is_featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->label('Featured')
                    ->toggleable(),
                
                TextColumn::make('date_started')
                    ->label('Start Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('contract_completion_date')
                    ->label('Completion Date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'on_hold' => 'On Hold',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                SelectFilter::make('publication_status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                
                SelectFilter::make('program_id')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('region_id')
                    ->relationship('region', 'name')
                    ->searchable()
                    ->preload(),
                
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All projects'),
                
                TernaryFilter::make('is_featured')
                    ->label('Featured Status')
                    ->boolean()
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured')
                    ->placeholder('All projects'),
                
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from'),
                        \Filament\Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
    }
}