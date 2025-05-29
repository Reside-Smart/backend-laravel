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

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('text')
                    ->label('Review Text')
                    ->disabled()
                    ->columnSpan(2),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Review Owner')
                    ->disabled()
                    ->columnSpan(1),
                Forms\Components\Select::make('listing_id')
                    ->relationship('listing', 'name')
                    ->label('Property')
                    ->disabled()
                    ->columnSpan(1),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled()
                    ->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('text')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Reviewer'),
                Tables\Columns\TextColumn::make('listing.name')
                    ->searchable()
                    ->sortable()
                    ->label('Property'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('listing_id')
                    ->relationship('listing', 'name')
                    ->label('Property'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Reviewer'),
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
            'index' => Pages\ListReviews::route('/'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }
}
