<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Transaction Management';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_type')
                            ->label('Transaction Type')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid ($)')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('payment_status')
                            ->label('Payment Status')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Payment Method')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('check_in_date')
                            ->label('Check In Date')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('check_out_date')
                            ->label('Check Out Date')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Related Information')
                    ->schema([
                        Forms\Components\Select::make('listing_id')
                            ->relationship('listing', 'name')
                            ->label('Property')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('buyer_id')
                            ->relationship('buyer', 'name')
                            ->label('Buyer')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('seller_id')
                            ->relationship('seller', 'name')
                            ->label('Seller')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'rent' => 'success',
                        'sell' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'unpaid',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Check In')
                    ->date('M d, Y'),
                Tables\Columns\TextColumn::make('listing.name')
                    ->label('Property')
                    ->searchable(),
                Tables\Columns\TextColumn::make('buyer.name')
                    ->label('Buyer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options([
                        'rent' => 'Rent',
                        'sell' => 'Sell',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'credit_card' => 'Credit Card',
                    ]),
                Tables\Filters\SelectFilter::make('listing_id')
                    ->relationship('listing', 'name')
                    ->searchable()
                    ->label('Property'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for read-only
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relations needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
