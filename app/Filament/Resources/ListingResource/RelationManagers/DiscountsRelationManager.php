<?php

namespace App\Filament\Resources\ListingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'expired',
                        'gray' => 'deactivated',
                    ]),
                Tables\Columns\TextColumn::make('rental_option.unit')
                    ->label('Rental Unit')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->rental_option_id
                            ? "{$record->rentalOption->duration} {$state}"
                            : 'All options'
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
                        'deactivated' => 'Deactivated',
                    ]),
                Tables\Filters\Filter::make('active_now')
                    ->query(function ($query) {
                        $now = Carbon::now();
                        return $query
                            ->where('status', 'active')
                            ->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now);
                    })
                    ->label('Currently Active'),
            ])
            ->headerActions([
                // No create action (read only)
            ])
            ->actions([])
            ->bulkActions([
                // No bulk actions (read only)
            ]);
    }
}
