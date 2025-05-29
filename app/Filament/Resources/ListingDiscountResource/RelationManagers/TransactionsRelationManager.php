<?php

namespace App\Filament\Resources\ListingDiscountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaction_type')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('amount_paid')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('payment_status')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('payment_method')
                    ->required()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('check_in_date')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('check_out_date')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('USD')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('USD')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->searchable(),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('payment_status')
                //     ->options([
                //         'paid' => 'Paid',
                //         'pending' => 'Pending',
                //         'failed' => 'Failed',
                //     ]),
                // Tables\Filters\SelectFilter::make('transaction_type')
                //     ->options([
                //         'rent' => 'Rent',
                //         'sell' => 'Sell',
                //     ]),
            ])
            ->headerActions([
                // No create action for read-only
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for read-only
            ]);
    }
}
