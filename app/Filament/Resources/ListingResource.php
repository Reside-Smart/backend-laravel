<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Filament\Resources\ListingResource\RelationManagers;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label("Listing Name")
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(191)
                                    ->placeholder('Enter Listing Name')
                                    ->columnSpan(1),
                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'sell' => 'Sell',
                                        'rent' => 'Rent',
                                    ])
                                    ->label('Transaction Type (Sell/Rent)')
                                    ->reactive()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('user_id')
                                    ->searchable()
                                    ->label('Owner')
                                    ->options(
                                        User::all()
                                            ->pluck('name', 'id')
                                            ->toArray()
                                    )
                                    ->required(),
                                Forms\Components\Select::make('category_id')
                                    ->searchable()
                                    ->label('Category')
                                    ->options(
                                        Category::all()
                                            ->pluck('name', 'id')
                                            ->toArray()
                                    )
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->placeholder('Enter Listing Price')
                                    ->required()
                                    ->numeric()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('renting_duration')
                                    ->required()
                                    ->numeric()
                                    ->placeholder(1)
                                    ->minValue(1)
                                    ->label('Renting Duration (in months)')
                                    ->columnSpan(1)
                                    ->disabled(function ($get) {
                                        return $get('type') === 'sell';
                                    })->nullable(function ($get) {
                                        return $get('type') === 'sell';
                                    }),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'published' => 'Published',
                                    ])
                                    ->required()
                                    ->label('Listing Status (Draft/Published)')
                                    ->columnSpan(1), Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->placeholder('Enter Description')
                                    ->label('Additional Description')
                                    ->columnSpan(2),

                            ])
                            ->columns(2),
                    ]),
                Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Repeater::make('features')
                                    ->schema([
                                        TextInput::make('key')
                                            ->label('Feature')
                                            ->placeholder("Bedrooms")
                                            ->required()
                                            ->rules(['distinct']),
                                        TextInput::make('value')
                                            ->label('Value')
                                            ->placeholder("3")
                                            ->required(),
                                    ])
                                    ->defaultItems(1)
                                    ->columns(2)
                                    ->reactive()
                                    ->columnSpanFull(),
                            ])
                    ]),
                Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\FileUpload::make('images')
                                    ->multiple()
                                    ->image()
                                    ->minFiles(0)
                                    ->maxFiles(6)
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/*'])
                                    ->validationMessages([
                                        'max_files' => 'You can upload a maximum of 6 images.',
                                        'max' => 'Each image must not exceed 2MB in size.',
                                    ])
                            ])->columns(2)
                    ]),
                Group::make()
                    ->schema([
                        // Password Section
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->required()
                                    ->placeholder('Enter Address')
                                    ->label('Listing Address')
                                    ->columnSpan(2),
                                Map::make('location')
                                    ->columnSpan(2)
                            ])->columns(2)
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->label('Owner Name'),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->searchable()
                    ->label("Price in $"),
                ImageColumn::make('images')
                    ->label('Listings Images')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'published' => 'primary',
                        'draft' => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'sell' => 'Sell',
                        'rent' => 'Rent',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->options(
                        Category::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )->label('Category'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->options(
                        User::all()
                            ->pluck('name', 'id')
                            ->toArray()
                    )->label('User'),
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
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}
