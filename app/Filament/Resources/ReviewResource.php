<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Filament\Resources\ReviewResource\RelationManagers;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rating')
                    ->placeholder('Enter rating')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->label('Rating (1-5)')
                    ->required(),
                Forms\Components\TextInput::make('text')
                    ->placeholder('Enter review text')
                    ->maxLength(255)
                    ->label('Review Text')
                    ->required(), Forms\Components\Select::make('user_id')
                    ->searchable()
                    ->label('Review Owner')
                    ->options(
                        User::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->required(),
                Forms\Components\Select::make('listing_id')
                    ->searchable()
                    ->label('Listing')
                    ->options(
                        Listing::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rating')
                    ->badge(),
                Tables\Columns\TextColumn::make('text'),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Review Owner'),
                Tables\Columns\TextColumn::make('listing.name')
                    ->searchable()
                    ->label('Listing')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('listing_id')
                    ->options(
                        Listing::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )->label('Listing'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->options(
                        User::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )->label('User'),
                // ])
                // ->actions([])
                // ->bulkActions([
                //     Tables\Actions\BulkActionGroup::make([
                //         Tables\Actions\DeleteBulkAction::make(),
                //     ]),
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
            'index' => Pages\ListReviews::route('/'),
            // 'create' => Pages\CreateReview::route('/create'),
            // 'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
