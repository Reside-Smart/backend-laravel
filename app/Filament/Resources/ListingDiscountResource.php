<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingDiscountResource\Pages;
use App\Filament\Resources\ListingDiscountResource\RelationManagers;
use App\Models\Listing;
use App\Models\ListingDiscount;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListingDiscountResource extends Resource
{
    protected static ?string $model = ListingDiscount::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->placeholder('Enter Name')
                                ->required(),
                            Forms\Components\TextInput::make('percentage')
                                ->placeholder('Enter Percentage')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100),
                            Forms\Components\Select::make('listing_id')
                                ->searchable()
                                ->label('Listing')
                                ->options(
                                    Listing::all()
                                        ->pluck('name', 'id')
                                        ->toArray()
                                )
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'inactive' => 'Inactive',
                                    'active' => 'Active',
                                ])
                                ->required()
                                ->columnSpan(1),

                        ])
                ]),
                Group::make()->schema([
                    Section::make()
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->displayFormat('F j, Y')
                                ->columnSpan(1)
                                ->minDate(fn (callable $get) => today()),

                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->displayFormat('F j, Y')
                                ->rules(['after:start_date'])
                                ->minDate(fn (callable $get) => $get('start_date') ?: now())
                                ->columnSpan(1),

                        ])
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('listing.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'primary',
                        'inactive' => 'warning',
                    })
                    ->searchable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('listing_id')
                    ->options(
                        Listing::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )->label('Listing'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'inactive' => 'Inactive',
                        'active' => 'Active',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListListingDiscounts::route('/'),
            'create' => Pages\CreateListingDiscount::route('/create'),
            'edit' => Pages\EditListingDiscount::route('/{record}/edit'),
        ];
    }
}
