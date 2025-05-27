<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Filament\Resources\ListingResource\RelationManagers;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Basic Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label("Property Name")
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('type')
                                    ->label('Transaction Type')
                                    ->options([
                                        'sell' => 'Sell',
                                        'rent' => 'Rent',
                                    ])
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('user_id')
                                    ->label('Owner')
                                    ->options(User::all()->pluck('name', 'id'))
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->options(Category::all()->pluck('name', 'id'))
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('price')
                                    ->label("Base Price ($)")
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('status')
                                    ->label('Publication Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'published' => 'Published',
                                    ])
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->disabled()
                                    ->columnSpan(2),
                            ])
                            ->columns(2)
                            ->collapsible(),
                    ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Location Information')
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->label('Property Address')
                                    ->disabled()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('latitude')
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('longitude')
                                    ->disabled()
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->collapsible(),
                        Forms\Components\Section::make('Features')
                            ->schema([
                                Forms\Components\Repeater::make('features')
                                    ->schema([
                                        Forms\Components\TextInput::make('key')
                                            ->label('Feature')
                                            ->disabled()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('value')
                                            ->label('Value')
                                            ->disabled()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2)
                                    ->disabled()
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),
                        Forms\Components\Section::make('Media')
                            ->schema([
                                Forms\Components\FileUpload::make('images')
                                    ->label('Property Images')
                                    ->multiple()
                                    ->disabled()
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->limit(2)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'rent' => 'success',
                        'sell' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Owner'),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available'),
                Tables\Columns\TextColumn::make('average_reviews')
                    ->label('Rating')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 1) . ' ★' : 'No ratings')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'rent' => 'Rent',
                        'sell' => 'Sell',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner'),
                Tables\Filters\Filter::make('is_available')
                    ->query(fn(Builder $query): Builder => $query->where('is_available', true))
                    ->label('Available Only'),
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
            RelationManagers\RentalOptionsRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\RatingsRelationManager::class,
            RelationManagers\DiscountsRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'view' => Pages\ViewListing::route('/{record}'),
        ];
    }
}
