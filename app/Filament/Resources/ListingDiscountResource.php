<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingDiscountResource\Pages;
use App\Filament\Resources\ListingDiscountResource\RelationManagers;
use App\Models\Listing;
use App\Models\ListingDiscount;
use App\Models\RentalOption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingDiscountResource extends Resource
{
    protected static ?string $model = ListingDiscount::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Discount Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\TextInput::make('percentage')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\Select::make('listing_id')
                            ->relationship('listing', 'name')
                            ->options(Listing::all()->pluck('name', 'id'))
                            ->preload()
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Date & Status')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->required()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'expired' => 'Expired',
                                'deactivated' => 'Deactivated',
                            ])
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->suffix('%')
                    ->searchable(),
                Tables\Columns\TextColumn::make('listing.name'),
                Tables\Columns\TextColumn::make('rentalOption.unit')
                    ->label('Unit Type')
                    ->formatStateUsing(fn($record) => $record->rental_option_id ?
                        "{$record->rentalOption->duration} {$record->rentalOption->unit}" :
                        'All Options')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'expired' => 'danger',
                        'deactivated' => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('listing_id')
                //     ->relationship('listing', 'name')
                //     ->searchable()
                //     ->preload()
                //     ->label('Listing'),
                // Tables\Filters\SelectFilter::make('status')
                //     ->options([
                //         'active' => 'Active',
                //         'inactive' => 'Inactive',
                //         'expired' => 'Expired',
                //         'deactivated' => 'Deactivated',
                //     ]),
                // Tables\Filters\Filter::make('date_range')
                //     ->form([
                //         Forms\Components\DatePicker::make('start_from'),
                //         Forms\Components\DatePicker::make('start_until'),
                //     ])
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['start_from'],
                //                 fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                //             )
                //             ->when(
                //                 $data['start_until'],
                //                 fn(Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                //             );
                //     })
                //     ->indicateUsing(function (array $data): array {
                //         $indicators = [];

                //         if ($data['start_from'] ?? null) {
                //             $indicators['start_from'] = 'Starts from ' . $data['start_from']->format('M j, Y');
                //         }

                //         if ($data['start_until'] ?? null) {
                //             $indicators['start_until'] = 'Starts until ' . $data['start_until']->format('M j, Y');
                //         }

                //         return $indicators;
                //     }),
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
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListingDiscounts::route('/'),
            'view' => Pages\ViewListingDiscount::route('/{record}'),
        ];
    }
}
